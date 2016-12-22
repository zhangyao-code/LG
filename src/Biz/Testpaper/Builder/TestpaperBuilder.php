<?php
namespace Biz\Testpaper\Builder;

use Topxia\Common\ArrayToolkit;
use Codeages\Biz\Framework\Context\Biz;
use Topxia\Service\Common\ServiceKernel;
use Topxia\Common\Exception\RuntimeException;
use Biz\Testpaper\Builder\TestpaperBuilderInterface;

class TestpaperBuilder implements TestpaperBuilderInterface
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function build($fields)
    {
        $fields = $this->filterFields($fields);

        $testpaper = $this->getTestpaperService()->createTestpaper($fields);

        $testpaperPattern = $this->getTestpaperService()->getTestpaperPattern($testpaper['pattern']);

        $testpaper['metas']['courseId'] = $testpaper['courseSetId'];

        $result = $testpaperPattern->getTestpaperQuestions($testpaper, $testpaper['metas']);

        if ($result['status'] != 'ok') {
            throw new \RuntimeException("Build testpaper #{$result['id']} items error.");
        }

        $this->createQuestionItems($result['items']);

        return $testpaper;
    }

    public function canBuild($options)
    {
        $questions      = $this->getQuestions($options);
        $typedQuestions = ArrayToolkit::group($questions, 'type');
        return $this->canBuildWithQuestions($options, $typedQuestions);
    }

    public function showTestItems($testId, $resultId = 0)
    {
        $test  = $this->getTestpaperService()->getTestpaper($testId);
        $items = $this->getTestpaperService()->findItemsByTestId($test['id']);
        if (!$items) {
            return array();
        }

        $itemResults = array();
        if (!empty($resultId)) {
            $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);

            $itemResults = $this->getTestpaperService()->findItemResultsByResultId($testpaperResult['id']);
            $itemResults = ArrayToolkit::index($itemResults, 'questionId');
        }

        $questionIds = ArrayToolkit::column($items, 'questionId');
        $questions   = $this->getQuestionService()->findQuestionsByIds($questionIds);

        $formatItems = array();
        foreach ($items as $questionId => $item) {
            $question = empty($questions[$questionId]) ? array() : $questions[$questionId];

            if (!$question) {
                $question = array(
                    'id'        => $item['questionId'],
                    'isDeleted' => true,
                    'stem'      => $this->getServiceKernel()->trans('此题已删除'),
                    'score'     => 0,
                    'answer'    => '',
                    'type'      => $item['questionType']
                );
            }

            $question['score']     = $item['score'];
            $question['seq']       = $item['seq'];
            $question['missScore'] = $item['missScore'];

            if (!empty($itemResults[$questionId])) {
                $question['testResult'] = $itemResults[$questionId];
            }

            if ($item['parentId'] > 0) {
                $formatItems['material'][$item['parentId']]['subs'][$questionId] = $question;
            } else {
                $formatItems[$item['questionType']][$questionId] = $question;
            }
        }

        return $formatItems;
    }

    public function filterFields($fields, $mode = 'create')
    {
        if (!empty($fields['mode'])) {
            $fields['metas']['mode'] = $fields['mode'];
        }
        if (!empty($fields['ranges'])) {
            $fields['metas']['ranges'] = $fields['ranges'];
        }
        if (!empty($fields['counts'])) {
            $fields['metas']['counts'] = $fields['counts'];
        }
        if (!empty($fields['scores'])) {
            $fields['metas']['scores'] = $fields['scores'];
        }
        if (!empty($fields['missScores'])) {
            $fields['metas']['missScores'] = $fields['missScores'];
        }
        if (!empty($fields['percentages'])) {
            $fields['metas']['percentages'] = $fields['percentages'];
        }

        $fields = ArrayToolkit::parts($fields, array(
            'name',
            'description',
            'courseId',
            'courseSetId',
            'lessonId',
            'type',
            'status',
            'limitedTime',
            'score',
            'passedCondition',
            'itemCount',
            'copyId',
            'pattern',
            'metas'
        ));

        return $fields;
    }

    public function updateSubmitedResult($resultId, $usedTime)
    {
        $testpaperResult = $this->getTestpaperService()->getTestpaperResult($resultId);
        $testpaper       = $this->getTestpaperService()->getTestpaper($testpaperResult['testId']);
        $items           = $this->getTestpaperService()->findItemsByTestId($testpaperResult['testId']);
        $itemResults     = $this->getTestpaperService()->findItemResultsByResultId($testpaperResult['id']);

        $questionIds = ArrayToolkit::column($items, 'questionId');

        $hasEssay = $this->getQuestionService()->hasEssay($questionIds);

        $fields = array(
            'status' => $hasEssay ? 'reviewing' : 'finished'
        );

        $accuracy                 = $this->getTestpaperService()->sumScore($itemResults);
        $fields['objectiveScore'] = $accuracy['sumScore'];

        $fields['score'] = 0;

        if (!$hasEssay) {
            $fields['score']       = $fields['objectiveScore'];
            $fields['checkedTime'] = time();
        }

        $fields['passedStatus'] = $fields['score'] >= $testpaper['passedCondition'][0] ? 'passed' : 'unpassed';

        $fields['usedTime'] = $usedTime + $testpaperResult['usedTime'];
        $fields['endTime']  = time();

        $fields['rightItemCount'] = $accuracy['rightItemCount'];

        return $this->getTestpaperService()->updateTestpaperResult($testpaperResult['id'], $fields);
    }

    protected function createQuestionItems($questions)
    {
        $testpaperItems = array();
        $seq            = 1;

        foreach ($questions as $item) {
            $questionType = $this->getQuestionService()->getQuestionConfig($item['questionType']);

            $item['seq'] = $seq;

            if (!$item['parentId']) {
                $seq++;
            }

            $testpaperItems[] = $this->getTestpaperService()->createItem($item);
        }

        return $testpaperItems;
    }

    protected function getQuestions($options)
    {
        $conditions        = array();
        $options['ranges'] = array_filter($options['ranges']);

        if (!empty($options['ranges'])) {
            $conditions['lessonIds'] = $options['ranges'];
        }
        $conditions['courseId'] = $options['courseId'];
        $conditions['parentId'] = 0;

        $total = $this->getQuestionService()->searchCount($conditions);

        return $this->getQuestionService()->search($conditions, array('createdTime' => 'DESC'), 0, $total);
    }

    protected function canBuildWithQuestions($options, $questions)
    {
        $missing = array();

        foreach ($options['counts'] as $type => $needCount) {
            $needCount = intval($needCount);
            if ($needCount == 0) {
                continue;
            }

            if (empty($questions[$type])) {
                $missing[$type] = $needCount;
                continue;
            }
            if ($type == "material") {
                $validatedMaterialQuestionNum = 0;
                foreach ($questions["material"] as $materialQuestion) {
                    if ($materialQuestion['subCount'] > 0) {
                        $validatedMaterialQuestionNum += 1;
                    }
                }
                if ($validatedMaterialQuestionNum < $needCount) {
                    $missing["material"] = $needCount - $validatedMaterialQuestionNum;
                }
                continue;
            }
            if (count($questions[$type]) < $needCount) {
                $missing[$type] = $needCount - count($questions[$type]);
            }
        }

        if (empty($missing)) {
            return array('status' => 'yes');
        }

        return array('status' => 'no', 'missing' => $missing);
    }

    protected function getTestpaperService()
    {
        return $this->biz->service('Testpaper:TestpaperService');
    }

    protected function getQuestionService()
    {
        return $this->biz->service('Question:QuestionService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
