<?php

namespace Biz\Activity\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\ActivityException;
use Biz\Activity\Dao\LiveActivityDao;
use Biz\Activity\LiveActivityException;
use Biz\Activity\Service\LiveActivityService;
use Biz\AppLoggerConstant;
use Biz\BaseService;
use Biz\Course\Service\LiveReplayService;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\User\Service\UserService;
use Biz\User\UserException;
use Biz\Util\EdusohoLiveClient;
use Codeages\Biz\Framework\Event\Event;
use Biz\Activity\Dao\ActivityDao;

class LiveActivityServiceImpl extends BaseService implements LiveActivityService
{
    private $client;

    public function getLiveActivity($id)
    {
        return $this->getLiveActivityDao()->get($id);
    }

    public function findLiveActivitiesByIds($ids)
    {
        return $this->getLiveActivityDao()->findByIds($ids);
    }

    public function findActivityByLiveActivityId($id)
    {
        $liveActivity = $this->getLiveActivityDao()->get($id);
        if (empty($liveActivity)) {
            $this->createNewException(LiveActivityException::NOTFOUND_LIVE());
        }
        $conditions = array(
            'mediaId' => $liveActivity['id'],
            'mediaType' => 'live',
        );
        $activities = $this->getActivityDao()->search($conditions, array('endTime' => 'DESC'), 0, 1);
        if (empty($activities)) {
            $this->createNewException(ActivityException::NOTFOUND_ACTIVITY());
        }
        if (!isset($activities[0])) {
            $this->createNewException(ActivityException::NOTFOUND_ACTIVITY());
        }
        $activity = array_merge($activities[0], $liveActivity);

        return $activity;
    }

    public function createLiveActivity($activity, $ignoreValidation = false)
    {
        if (!$ignoreValidation && (empty($activity['startTime'])
                || $activity['startTime'] <= time()
                || empty($activity['length'])
                || $activity['length'] <= 0)
        ) {
            $this->createNewException(LiveActivityException::LIVE_TIME_INVALID());
        }

        //???????????????
        if (empty($activity['startTime'])
            || $activity['startTime'] <= time()
        ) {
            //???????????????????????????
            $live = array(
                'id' => 0,
                'provider' => 0,
            );
        } else {
            $live = $this->createLiveroom($activity);

            if (empty($live)) {
                $this->createNewException(LiveActivityException::CREATE_LIVEROOM_FAILED());
            }

            if (isset($live['error'])) {
                $error = '???????????????' == $live['error'] ? '?????????????????????' : $live['error'];
                throw $this->createServiceException($error);
            }
            $this->dispatchEvent('live.activity.create', new Event($live['id'], array('activity' => $activity)));
        }

        if (!empty($activity['roomType']) && !$this->isRoomType($activity['roomType'])) {
            $this->createNewException(LiveActivityException::ROOMTYPE_INVALID());
        }

        $liveActivity = array(
            'liveId' => $live['id'],
            'liveProvider' => $live['provider'],
            'roomType' => empty($activity['roomType']) ? EdusohoLiveClient::LIVE_ROOM_LARGE : $activity['roomType'],
            'roomCreated' => $live['id'] > 0 ? 1 : 0,
        );

        return $this->getLiveActivityDao()->create($liveActivity);
    }

    public function updateLiveActivity($id, $fields, $activity)
    {
        $preLiveActivity = $liveActivity = $this->getLiveActivityDao()->get($id);

        if (empty($liveActivity)) {
            return array();
        }
        $fields = array_merge($activity, $fields);
        if (!$liveActivity['roomCreated']) {
            if ($fields['startTime'] > time()) {
                $live = $this->createLiveroom($fields);
                $liveActivity['liveId'] = $live['id'];
                $liveActivity['liveProvider'] = $live['provider'];
                $liveActivity['roomCreated'] = 1;
                $liveActivity['roomType'] = empty($fields['roomType']) ? EdusohoLiveClient::LIVE_ROOM_LARGE : $fields['roomType'];

                $liveActivity = $this->getLiveActivityDao()->update($id, $liveActivity);
            }
        } elseif ($fields['endTime'] > time()) {
            //?????????????????????????????????????????????????????????
            $liveParams = array(
                'liveId' => $liveActivity['liveId'],
                'summary' => empty($fields['remark']) ? '' : $fields['remark'],
                'title' => $fields['title'],
            );
            //???????????????????????????????????????????????????
            if ($fields['startTime'] > time()) {
                $liveParams['startTime'] = $fields['startTime'];
                $liveParams['endTime'] = (string) ($fields['startTime'] + $fields['length'] * 60);
            }

            if (!empty($fields['roomType']) && $this->canUpdateRoomType($activity['startTime'])) {
                $liveParams['roomType'] = $fields['roomType'];
            }

            $this->getEdusohoLiveClient()->updateLive($liveParams);
        }
        $live = ArrayToolkit::parts($fields, array('replayStatus', 'fileId', 'roomType'));

        if (!empty($live['fileId'])) {
            $live['mediaId'] = $live['fileId'];
            $live['replayStatus'] = LiveReplayService::REPLAY_VIDEO_GENERATE_STATUS;
        }

        unset($live['fileId']);

        if (!empty($live)) {
            $liveActivity = $this->getLiveActivityDao()->update($id, $live);
        }

        $this->dispatchEvent('live.activity.update', new Event($liveActivity, array('fields' => $live, 'liveId' => $liveActivity['liveId'], 'activity' => $activity)));

        return array($liveActivity, $fields);
    }

    public function updateLiveStatus($id, $status)
    {
        $liveActivity = $this->getLiveActivityDao()->get($id);
        if (empty($liveActivity)) {
            return;
        }

        if (!in_array($status, array(EdusohoLiveClient::LIVE_STATUS_LIVING, EdusohoLiveClient::LIVE_STATUS_CLOSED, EdusohoLiveClient::LIVE_STATUS_PAUSE))) {
            $this->createNewException(LiveActivityException::LIVE_STATUS_INVALID());
        }

        $update = $this->getLiveActivityDao()->update($liveActivity['id'], array('progressStatus' => $status));
        $this->getLogService()->info(AppLoggerConstant::LIVE, 'update_live_status', "?????????????????????????????????{$liveActivity['progressStatus']}????????????{$status}???", array('preLiveActivity' => $liveActivity, 'newLiveActivity' => $update));

        return $update;
    }

    public function deleteLiveActivity($id)
    {
        //???????????????
        $liveActivity = $this->getLiveActivityDao()->get($id);
        if (empty($liveActivity)) {
            return;
        }

        $this->getLiveActivityDao()->delete($id);
        if (!empty($liveActivity['liveId'])) {
            $this->getEdusohoLiveClient()->deleteLive($liveActivity['liveId']);
            $this->dispatchEvent('live.activity.delete', new Event($liveActivity['liveId']));
        }
    }

    public function search($conditions, $orderbys, $start, $limit)
    {
        return $this->getLiveActivityDao()->search($conditions, $orderbys, $start, $limit);
    }

    public function getByLiveId($liveId)
    {
        return $this->getLiveActivityDao()->getByLiveId($liveId);
    }

    /**
     * ?????????????????? roomType??? ???????????????10???????????????????????????????????????
     *
     * @param [type] $liveStartTime ??????????????????
     *
     * @return bool
     */
    public function canUpdateRoomType($liveStartTime)
    {
        $timeDiff = $liveStartTime - time();
        $disableSeconds = 3600 * 2;

        if ($timeDiff < 0 || ($timeDiff > 0 && $timeDiff <= $disableSeconds)) {
            return 0;
        }

        return 1;
    }

    protected function isRoomType($liveRoomType)
    {
        return in_array($liveRoomType, array(EdusohoLiveClient::LIVE_ROOM_LARGE, EdusohoLiveClient::LIVE_ROOM_SMALL));
    }

    /**
     * @return LiveActivityDao
     */
    protected function getLiveActivityDao()
    {
        return $this->createDao('Activity:LiveActivityDao');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    public function getEdusohoLiveClient()
    {
        if (empty($this->client)) {
            $this->client = new EdusohoLiveClient();
        }

        return $this->client;
    }

    /**
     * @param  $activity
     *
     * @throws \Biz\User\UserException
     * @throws \Biz\Activity\LiveActivityException
     * @throws \Exception
     *
     * @return array
     */
    public function createLiveroom($activity)
    {
        $speaker = $this->getUserService()->getUser($activity['fromUserId']);
        if (empty($speaker)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!empty($activity['roomType']) && !$this->isRoomType($activity['roomType'])) {
            $this->createNewException(LiveActivityException::ROOMTYPE_INVALID());
        }

        $speaker = $speaker['nickname'];

        $liveLogo = $this->getSettingService()->get('course');
        $liveLogoUrl = '';
        $baseUrl = $this->biz['env']['base_url'];
        if (!empty($liveLogo) && array_key_exists('live_logo', $liveLogo) && !empty($liveLogo['live_logo'])) {
            $liveLogoUrl = $baseUrl.'/'.$liveLogo['live_logo'];
        }

        $live = $this->getEdusohoLiveClient()->createLive(array(
            'summary' => empty($activity['remark']) ? '' : $activity['remark'],
            'title' => $activity['title'],
            'speaker' => $speaker,
            'startTime' => $activity['startTime'].'',
            'endTime' => ($activity['startTime'] + $activity['length'] * 60).'',
            'authUrl' => $baseUrl.'/live/auth',
            'jumpUrl' => $baseUrl.'/live/jump?id='.$activity['fromCourseId'],
            'liveLogoUrl' => $liveLogoUrl,
            'roomType' => empty($activity['roomType']) ? EdusohoLiveClient::LIVE_ROOM_LARGE : $activity['roomType'],
        ));

        return $live;
    }

    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return ActivityDao
     */
    protected function getActivityDao()
    {
        return $this->createDao('Activity:ActivityDao');
    }

    protected function getSchedulerService()
    {
        return $this->createService('Scheduler:SchedulerService');
    }
}
