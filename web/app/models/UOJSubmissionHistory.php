<?php

class UOJSubmissionHistory {
    use UOJDataTrait;

    public $submission = null;
    public $n_versions;

    public static $fields_without_result = [
        'tid' => 'id', 'message' => 'judge_reason', 'time' => 'judge_time',
        'judger', 'status', 'result_error',
        'actual_score' => 'score', 'used_time', 'used_memory',
        'type' => 'if(major, "major", "minor")'
    ];

    public static $fields = [
        'tid' => 'id', 'message' => 'judge_reason', 'time' => 'judge_time',
        'judger', 'result', 'status', 'result_error',
        'actual_score' => 'score', 'used_time', 'used_memory',
        'type' => 'if(major, "major", "minor")'
    ];

    public static function query(UOJSubmission $submission, $cfg = []) {
        $cfg += [
            'minor' => false,
            'system' => true
        ];

        $q = [
            "select", DB::fields([
                'tid' => DB::value(0),
                'message' => 'judge_reason',
                'time' => DB::if_func(['judge_time' => null], UOJTime::MAX_TIME, DB::raw('judge_time')),
                'judger', 'status', 'result_error',
                'actual_score' => UOJSubmission::sqlForActualScore(), 'used_time', 'used_memory',
                'type' => DB::value('major'),
                'priority' => 1000,
            ]), "from submissions",
            "where", ["id" => $submission->info['id']],
            "union all",
            "select", DB::fields(
                UOJSubmissionHistory::$fields_without_result + [
                'priority' => 100
            ]),
            "from submissions_history",
            "where", [
                'submission_id' => $submission->info['id'],
                ['judge_time', 'is not', null]
            ] + ($cfg['minor'] ? [] : ['major' => true]),
            "union all",
            "select", DB::fields([
                'tid' => DB::value(-1),
                'message',
                'time',
                'judger' => DB::value(null), 'status' => DB::value(null), 'result_error' => DB::value(null),
                'actual_score' => DB::value(null), 'used_time' => DB::value(null), 'used_memory' => DB::value(null),
                'type',
                'priority' => 99
            ]), "from system_updates",
            "where", [
                'type' => 'problem',
                'target_id' => $submission->info['problem_id']
            ],
            "union all",
            "select", DB::fields([
                'tid' => DB::value(-1),
                'message' => DB::value(''),
                'time' => 'submit_time',
                'judger' => DB::value(null), 'status' => DB::value(null), 'result_error' => DB::value(null),
                'actual_score' => DB::value(null), 'used_time' => DB::value(null), 'used_memory' => DB::value(null),
                'type' => DB::value('submit'),
                'priority' => 10
            ]), "from submissions",
            "where", ["id" => $submission->info['id']],
        ];
        if ($cfg['system']) {
            $q = array_merge($q, [
                "union all",
                "select", DB::fields([
                    'tid' => DB::value(-1),
                    'message',
                    'time',
                    'judger' => DB::value(null), 'status' => DB::value(null), 'result_error' => DB::value(null),
                    'actual_score' => DB::value(null), 'used_time' => DB::value(null), 'used_memory' => DB::value(null),
                    'type',
                    'priority' => 10
                ]), "from system_updates",
                "where", [
                    ['type', 'in', DB::rawtuple(['judge', 'submissions_history'])]
                ]
            ]);
        }
        $q = array_merge($q, [
            "order by time, priority asc"
        ]);

        $ret = DB::selectAll($q);
        if ($ret === false) {
            return null;
        }

        $st = null;
        $ed = null;
        foreach ($ret as $idx => &$his) {
            if ($his['type'] == 'major' || $his['type'] == 'minor' || $his['type'] == 'submit') {
                $ed = $idx;
                if ($his['type'] == 'submit') {
                    $st = $idx;
                }
            }
            if ($his['time'] == UOJTime::MAX_TIME) {
                $his['time'] = null;
            }
        }

        $res = [];
        foreach ($ret as $idx => &$his) {
            if ($st !== null && $idx >= $st) {
                $res[] = $his;
            }
        }
        $res = array_reverse($res);
        return new UOJSubmissionHistory($res, $submission);
    }

    public function __construct($info, UOJSubmission $submission) {
        $this->info = $info;
        $this->submission = $submission;

        $this->n_versions = 0;
        foreach ($this->info as $his) {
            if ($his['type'] == 'major' || $his['type'] == 'minor') {
                $this->n_versions++;
            }
        }
    }

    public function echoTimeline() {
        echo '<div class="list-group timeline">';
        if ($this->submission->isLatest()) {
            echo '<p class="text-success"><i class="bi bi-check-circle"></i> 你现在查看的是最新测评结果</p>';
        } else if (UOJSubmission::info('judge_time') !== null) {
            if ($this->submission->isMajor()) {
                echo '<p class="text-danger"><i class="bi bi-exclamation-circle"></i> 你现在查看的是测评时间为 ', UOJSubmission::info('judge_time'),' 的历史记录</p>';
            } else {
                echo '<p class="text-warning"><i class="bi bi-exclamation-circle"></i> 你现在查看的是测评时间为 ', UOJSubmission::info('judge_time'),' 的隐藏记录</p>';
            }
        }
        $h = clone $this->submission;
        $cfg = [
            'show_actual_score' => true
        ];
        foreach ($this->info as $his) {
            $message = json_decode($his['message'], true);
            $show_result = true;

            $extra = null;

            if ($his['type'] == 'major' || $his['type'] == 'minor') {
                $h->loadHistory($his);
                $cls = 'list-group-item rounded-0';
                if ($h->info['tid'] == $this->submission->getTID()) {
                    $cls .= ' list-group-item-warning';
                }
                echo '<div class="', $cls, '">';
                $extra = '<a class="text-decoration-none" href="'.$h->getUri().'"><i class="bi bi-info-circle"></i> 查看</a>';
                $split_cls = ['col-sm-10', 'col-sm-2'];
            } else {
                $show_result = false;
                echo '<div class="list-group-item rounded-0">';
            }

            if ($extra) {
                echo '<div class="row align-items-center">';
                echo '<div class="', $split_cls[0], '">';
            }

            echo '<ul class="list-group-item-text list-inline text-primary mb-1">';
            echo '<li class="list-inline-item">';
            if ($his['time'] !== null) {
                echo '<strong>[', $his['time'], ']</strong>';
            } else {
                $show_result = false;
                if ($his['type'] == 'major' || $his['type'] == 'minor') {
                    echo '<strong>[', $h->echoStatusBarTD('result', $cfg), ']</strong>';
                } else {
                    echo '<strong>[error]</strong>';
                }
            }
            echo '</li>';

            echo '<li class="list-inline-item">';
            if (empty($message['text'])) {
                if ($his['type'] == 'submit') {
                    echo '提交';
                } else {
                    echo '评测';
                }
            } else {
                echo HTML::escape($message['text']);
            }
            echo '</li>';

            echo '<li class="list-inline-item">';
            if (!empty($message['url'])) {
                echo '(', HTML::autolink($message['url']), ')';
            }
            echo '</li>';
            echo '</ul>';

            if ($show_result) {
                echo '<ul class="-text list-inline mb-0">';
                echo '<li class="list-inline-item">';
                echo '<strong>测评结果：</strong>';
                echo $h->echoStatusBarTD('result', $cfg);
                echo '</li>';
                echo '<li class="list-inline-item">';
                echo '<strong>用时：</strong>';
                echo $h->echoStatusBarTD('used_time', $cfg);
                echo '</li>';
                echo '<li class="list-inline-item">';
                echo '<strong>内存：</strong>';
                echo $h->echoStatusBarTD('used_memory', $cfg);
                echo '</li>';
                echo '</ul>';
            }

            if ($extra) {
                echo '</div>'; // col-md-9
                echo '<div class="', $split_cls[1],' text-end">', $extra, '</div>';
                echo '</div>'; // row
            }

            echo '</div>'; // 
        }
        echo '</div>';
    }
}
