<?php
// GET /lesson - chỉ load câu hỏi, không xử lý hoàn thành
$app->router("/lesson", 'GET', function($vars) use ($app) {
    // Chỉ include header khi không phải request Ajax
    $isAjax = (isset($_GET['ajax']) && $_GET['ajax'] == 1)
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!$isAjax) {
        require_once __DIR__ . '/../home/headerhome.php';
    }
    $isAjax = (isset($_GET['ajax']) && $_GET['ajax'] == 1)
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
    }

    try {
        // Khởi tạo session stats nếu chưa có
        if (!isset($_SESSION['lesson_stats'])) {
            $_SESSION['lesson_stats'] = [
                'answered' => 0,
                'correct' => 0,
                'score' => 0,
                'duration' => 0
            ];
            $_SESSION['status'] = '';
        }
        if (isset($_GET['lesson_id'])) {
            $_SESSION['lesson_id'] = intval($_GET['lesson_id']);
        }
        $lesson_id = $_SESSION['lesson_id'] ?? 0;
        $ids = $lesson_id > 0 ? $app->select("questions", ["question_id"], ["lesson_id" => $lesson_id]) : [];
        if (empty($ids)) {
            throw new Exception('Bài học không có câu hỏi');
        }

        // Nếu đã đủ 10 câu, chuyển hướng sang /lesson-complete
        if ($_SESSION['lesson_stats']['answered'] >= 10) {
                // Gộp xử lý hoàn thành vào đây, không chuyển hướng nữa
                $stats = $_SESSION['lesson_stats'];
                $vars['answered'] = $stats['answered'];
                $vars['correct'] = $stats['correct'];
                $vars['score'] = $stats['score'];
                $vars['duration'] = $stats['duration'];

                // Lưu vào bảng test trước khi reset
                $app->insert("test", [
                    "id_account" => $_SESSION['account_id'] ?? 16,
                    "id_lesson"  => $_SESSION['lesson_id'] ?? 0,
                    "point"      => $stats['score'],
                    "answer"     => $stats['answered'],
                    "wrong"     => $stats['answered'] - $stats['correct'],
                    "time"       => $stats['duration'],
                    "date"       => date('Y-m-d H:i:s'),
                    "deleted"    => 0
                ]);

                // Reset lại stats cho lần sau
                $_SESSION['lesson_stats'] = [
                    'answered' => 0,
                    'correct' => 0,
                    'score' => 0,
                    'duration' => 0
                ];
                $_SESSION['status'] = '';
                $_SESSION['lesson_id'] = 0;

                echo $app->render('templates/lessons/test-completed.html', $vars);
            //}
            return;
        }

        // Chọn câu hỏi ngẫu nhiên
        $id = $ids[array_rand($ids)]['question_id'];
        if (isset($_SESSION['status']) && $_SESSION['status'] == 'false') {
            $id = $_SESSION['false'];
        }

        // Lấy thông tin câu hỏi
        $question = $app->get("questions", "*", ["question_id" => $id]);
        if (!$question) {
            throw new Exception('Không tìm thấy câu hỏi');
        }

        // Fix encoding
        $question = array_map(function($value) {
            return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'auto') : $value;
        }, $question);

        // Chuẩn bị response
        $response = [
            'status' => 'success',
            'question' => [
                'question_id' => (int)$question['question_id'],
                'question_text' => $question['question_text'],
                'type' => $app->get("question_types", "type_name", ["type_id" => $question['type_id']]),
                'picture' => $question['picture'],
                'difficulty' => (int)$question['difficulty'],
                'explanation' => $question['explanation'],
                'hints' => array_map(function($hint) {
                    return mb_convert_encoding($hint['hint_text'], 'UTF-8', 'auto');
                }, $app->select("hints", ["hint_text"], ["question_id" => $id]))
            ],
            'stats' => $_SESSION['lesson_stats']
        ];

        // Xử lý loại câu hỏi
        if ($response['question']['type'] === 'multiple_choice') {
            $response['question']['choices'] = array_map(function($choice) {
                return [
                    'choice_id' => (int)$choice['choice_id'],
                    'choice_text' => mb_convert_encoding($choice['choice_text'], 'UTF-8', 'auto')
                ];
            }, $app->select("choices", ["choice_id", "choice_text"], ["question_id" => $id]));
        } else {
            $open_answer = $app->get("open_answers", "*", ["question_id" => $id]);
            if ($open_answer) {
                $response['question']['answer_format'] = $open_answer['answer_format'];
            }
        }

        // Thêm thông tin nếu trả lời sai
        if (isset($_SESSION['status']) && $_SESSION['status'] == 'false') {
            $response['answer_wrong'] = $_SESSION['answer_wrong'] ?? null;
            $response['correct_choice'] = $_SESSION['correct_choice'] ?? null;
        }

        // Nếu chưa trả lời gì thì status phải là rỗng
        if (!isset($_SESSION['status'])) {
            $response['status'] = '';
        } else {
            $response['status'] = $_SESSION['status'];
        }

        // Trả về JSON đã chuẩn hóa
        if ($isAjax) {
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            // Nếu là truy cập web bình thường, trả về HTML
            echo $app->render('templates/lessons/lesson.html', $vars);
        }
    } catch (Exception $e) {
        http_response_code(500);
        if ($isAjax) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Xử lý lỗi cho truy cập web bình thường (có thể render một trang lỗi riêng)
            echo $app->render('templates/error.html', ['message' => $e->getMessage()]);
        }
    }
});

$app->router("/lesson", 'POST', function($vars) use ($app) {
    $app->header([
        'Content-Type' => 'application/json',
    ]);
    if (!isset($_SESSION['lesson_stats'])) {
        $_SESSION['lesson_stats'] = [
            'answered' => 0,
            'correct' => 0,
            'score' => 0,
            'duration' => 0
        ];
    }
    $question_id = intval($_POST['question_id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    $client_duration = intval($_POST['duration'] ?? 0);
    $_SESSION['lesson_stats']['duration'] = $client_duration;

    // Xử lý khi bấm "Tiếp tục" sau khi đúng/sai
    if (
        isset($_POST['status']) && (($_POST['status'] === 'false' || $_POST['status'] === 'success'))
        && $_SESSION['lesson_stats']['answered'] >= 10
    ) {
        echo json_encode([
            'status' => 'completed',
            'stats' => $_SESSION['lesson_stats'],
            'redirect' => '/lesson'
        ]);
        return;
    }

    if (isset($_POST['status']) && $_POST['status'] === 'false') {
        unset($_SESSION['false'], $_SESSION['answer_wrong'], $_SESSION['correct_choice']);
        unset($_SESSION['status']);
        echo json_encode([
            'status' => '',
            'stats' => $_SESSION['lesson_stats']
        ]);
        return;
    }
    if (isset($_POST['status']) && $_POST['status'] === 'success') {
        unset($_SESSION['status']);
        echo json_encode([
            'status' => '',
            'stats' => $_SESSION['lesson_stats']
        ]);
        return;
    }

    if (!$question_id || $answer === '') {
        echo json_encode(['status' => 'error', 'content' => 'Không được để trống']);
        return;
    }

    // Lấy thông tin câu hỏi
    $question = $app->get("questions", "*", ["question_id" => $question_id]);
    if (!$question) {
        echo json_encode(['status' => 'error', 'content' => 'Câu hỏi không tồn tại']);
        return;
    }

    // Lấy loại câu hỏi
    $type = $app->get("question_types", "*", ["type_id" => $question['type_id']]);
    $type_name = $type['type_name'];

    $result = [
        'status' => '', // Sửa: mặc định là rỗng, chỉ set 'success' hoặc 'false' khi thực sự đúng/sai
        'message' => '',
    ];
    if ($type_name === 'multiple_choice') {
        $correct_choice = $app->get("choices", "*", [
            "question_id" => $question_id,
            "is_correct" => 1
        ]);
        if ($correct_choice && intval($answer) === intval($correct_choice['choice_id'])) {
            $result['status'] = 'success';
            $result['message'] = 'Chính xác!';
            $_SESSION['status'] = 'success';
        } else {
            $result['status'] = 'false';
            $result['message'] = 'Sai đáp án!';
            $_SESSION['status'] = 'false';
            $_SESSION['false'] = $question_id;
            $_SESSION['answer_wrong'] = $answer;
            $_SESSION['correct_choice'] = $correct_choice['choice_id'];
        }
    } else {
        // Tự luận hoặc điền đáp án
        $open = $app->get("open_answers", "*", ["question_id" => $question_id]);
        if ($open) {
            $correct_answer = trim($open['correct_answer']);
            if (mb_strtolower($answer) == mb_strtolower($correct_answer)) {
                $result['status'] = 'success';
                $result['message'] = 'Chính xác!';
                $_SESSION['status'] = 'success';
            } else {
                $result['status'] = 'false';
                $result['message'] = 'Sai đáp án!';
                $_SESSION['status'] = 'false';
                $_SESSION['false'] = $question_id;
                $_SESSION['answer_wrong'] = $answer;
                $_SESSION['correct_choice'] = $open['correct_answer'];
            }
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Không tìm thấy đáp án';
        }
    }
    if ($result['status'] === 'success') {
        $_SESSION['lesson_stats']['answered']++;
        $_SESSION['lesson_stats']['correct']++;
        $_SESSION['lesson_stats']['score'] += 10;
    } else if ($result['status'] === 'false') {
        $_SESSION['lesson_stats']['answered']++;
        // Trả về đầy đủ thông tin câu hỏi khi sai
        $question_type = $app->get("question_types", "type_name", ["type_id" => $question['type_id']]);
        $response = [
            'status' => 'false',
            'content' => $result['message'],
            'stats' => [
                'answered' => $_SESSION['lesson_stats']['answered'],
                'correct' => $_SESSION['lesson_stats']['correct'],
                'score' => $_SESSION['lesson_stats']['score'],
                'duration' => $_SESSION['lesson_stats']['duration']
            ],
            'answer_wrong' => $_SESSION['answer_wrong'] ?? null,
            'correct_choice' => $_SESSION['correct_choice'] ?? null,
            'question' => [
                'question_id' => (int)$question['question_id'],
                'question_text' => $question['question_text'],
                'type' => $question_type,
                'picture' => $question['picture'],
                'difficulty' => (int)$question['difficulty'],
                'explanation' => $question['explanation'],
            ]
        ];
        if ($question_type === 'multiple_choice') {
            $response['question']['choices'] = array_map(function($choice) {
                return [
                    'choice_id' => (int)$choice['choice_id'],
                    'choice_text' => mb_convert_encoding($choice['choice_text'], 'UTF-8', 'auto')
                ];
            }, $app->select("choices", ["choice_id", "choice_text"], ["question_id" => $question['question_id']]));
        }
        echo json_encode($response);
        return;
    }
    echo json_encode([
        'status' => $result['status'],
        'content' => $result['message'],
        'stats' => [
            'answered' => $_SESSION['lesson_stats']['answered'],
            'correct' => $_SESSION['lesson_stats']['correct'],
            'score' => $_SESSION['lesson_stats']['score'],
            'duration' => $_SESSION['lesson_stats']['duration']
        ],
        // Trả về thêm thông tin cho giao diện nếu sai
        'answer_wrong' => $_SESSION['answer_wrong'] ?? null,
        'correct_choice' => $_SESSION['correct_choice'] ?? null
    ]);
    return;
});
?>