<?php
$app->router("/lesson", 'GET', function($vars) use ($app) {
        //Lấy tất cả id câu hỏi
        require_once __DIR__ . '/../home/headerhome.php';
        if (!isset($_SESSION['lesson_stats'])) {
            // Chỉ khởi tạo nếu chưa có, không reset khi refresh
            $_SESSION['lesson_stats'] = [
                'answered' => 0,
                'correct' => 0,
                'score' => 0,
                'duration' => 0
            ];
        }
        // Nếu có lesson_id trên query string thì lưu vào session
        if (isset($_GET['lesson_id'])) {
            $_SESSION['lesson_id'] = intval($_GET['lesson_id']);
        }
        // Lấy lesson_id từ session
        $lesson_id = $_SESSION['lesson_id'] ?? 0;

        $ids = []; // Khởi tạo biến $ids để tránh lỗi undefined variable
        if ($lesson_id > 0) {
            $ids = $app->select("questions", ["question_id"], ["lesson_id" => $lesson_id]);
        }
        if (!$ids || count($ids) === 0) {
            echo $app->render('templates/error.html', $vars);
            return;
        }
        // Random 1 id trong số đó
        $randomIndex = array_rand($ids);
        $id = $ids[$randomIndex]['question_id'];
        if(isset($_SESSION['status']) && $_SESSION['status']=='false') {
            // Nếu đã trả lời sai câu trước đó, lấy lại id và thông tin câu hỏi
            $id = $_SESSION['false'];
            // ĐỪNG gán $vars["answer_wrong"] ở đây
        }
        // Lấy thông tin câu hỏi
        $question = $app->get("questions", "*", ["question_id" => $id]);
        if (!$question) {
            echo $app->render('templates/error.html', $vars);
            return;
        }

        // Lấy loại câu hỏi
        $type = $app->get("question_types", "*", ["type_id" => $question['type_id']]);
        $type_name = $type['type_name'];

        // Lấy gợi ý
        $hints = $app->select("hints", ["hint_text"], [
            "question_id" => $id,
            "ORDER" => ["display_order" => "ASC"]
        ]);
        $hintArr = [];
        foreach ($hints as $h) $hintArr[] = $h['hint_text'];

        $vars = [
            "question_id"   => $question['question_id'],
            "question_text" => $question['question_text'],
            'type'          => $type_name,
            "picture"     => $question['picture'],
            "difficulty"    => $question['difficulty'],
            "explanation"    => $question['explanation'],
            "hints"         => $hintArr
        ];

        // Nếu vừa trả lời sai, bổ sung các biến này vào $vars
        if(isset($_SESSION['status']) && $_SESSION['status']=='false') {
            $vars["answer_wrong"] = $_SESSION['answer_wrong'] ?? null;
            $vars["correct_choice"] = $_SESSION['correct_choice'] ?? null;
        }

        // Nếu là trắc nghiệm
        if ($type_name === 'multiple_choice') {
            $choices = $app->select("choices", ["choice_id", "choice_text"], [
                "question_id" => $id,
                "ORDER" => ["display_order" => "ASC"]
            ]);
            $vars['choices'] = $choices;
        }
        // Nếu là tự luận hoặc điền đáp án
        else {
            $open = $app->get("open_answers", "*", ["question_id" => $id]);
            if ($open) {
                $vars['answer_format'] = $open['answer_format'];
                // Không trả về đáp án đúng cho client!
            }
        }
        if (isset($_SESSION['status'])) {
            $vars['status'] = $_SESSION['status'];
            unset($_SESSION['status']);
        } else {
            $vars['status'] = '';
        }
        if ($_SESSION['lesson_stats']['answered'] >= 10 && $vars['status'] == '') {
            $vars['answered'] = $_SESSION['lesson_stats']['answered'];
            $vars['correct'] = $_SESSION['lesson_stats']['correct'];
            $vars['score'] = $_SESSION['lesson_stats']['score'];
            $vars['duration'] = $_SESSION['lesson_stats']['duration'];

            // Lưu vào bảng test trước khi reset
            $app->insert("test", [
                "id_account" => $_SESSION['account_id'] ?? 16,
                "id_lesson"  => $_SESSION['lesson_id'] ?? 0, // lấy id bài học động nếu có
                "point"      => $_SESSION['lesson_stats']['score'],
                "time"       => $_SESSION['lesson_stats']['duration'],
                "date"       => date('Y-m-d H:i:s'),
                "deleted"    => 0
            ]);

            // Reset lại stats cho lần sau (sau khi render)
            $_SESSION['lesson_stats'] = [
                'answered' => 0,
                'correct' => 0,
                'score' => 0,
                'duration' => 0
            ];
            $_SESSION['lesson_id'] = 0; // Reset lesson_id để không lặp lại bài cũ
            echo $app->render('templates/lessons/test-completed.html', $vars);
            return;
        }
        echo $app->render('templates/lessons/lesson.html', $vars);
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
        // Lấy duration từ client gửi lên
        $client_duration = intval($_POST['duration'] ?? 0);
        $_SESSION['lesson_stats']['duration'] = $client_duration;
        if (isset($_POST['status']) && $_POST['status'] === 'false') {
            // Sau khi người dùng bấm "Tiếp tục", xóa các biến session liên quan đến câu sai
            unset($_SESSION['false'], $_SESSION['answer_wrong'], $_SESSION['correct_choice']);
            echo json_encode([
                'status' => 'success',
                'content' => 'Bạn đã trả lời sai câu hỏi này!',
                'stats' => [
                    'answered' => $_SESSION['lesson_stats']['answered'],
                    'correct' => $_SESSION['lesson_stats']['correct'],
                    'score' => $_SESSION['lesson_stats']['score'],
                    'duration' => $_SESSION['lesson_stats']['duration']
                ]
            ]);
            unset($_SESSION['status']);
            return;
        }
        if (isset($_POST['status']) && $_POST['status'] === 'success') {
            echo json_encode([
                'status' => 'success',
                'content' => 'Bạn đã trả lời đúng câu hỏi này!',
                'stats' => [
                    'answered' => $_SESSION['lesson_stats']['answered'],
                    'correct' => $_SESSION['lesson_stats']['correct'],
                    'score' => $_SESSION['lesson_stats']['score'],
                    'duration' => $_SESSION['lesson_stats']['duration']
                ]
            ]);
            unset($_SESSION['status']);
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
            'status' => 'success',
            'message' => '',
        ];
        if ($type_name === 'multiple_choice') {
            // Lấy đáp án đúng
            $correct_choice = $app->get("choices", "*", [
                "question_id" => $question_id,
                "is_correct" => 1
            ]);
            if ($correct_choice && $answer == $correct_choice['choice_id']) {
                $result['message'] = 'Chính xác!';
                $_SESSION['status'] = 'success';
            } else {
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
                // So sánh đáp án (có thể cần xử lý nâng cao hơn)
                $correct_answer = trim($open['correct_answer']);
                if (mb_strtolower($answer) == mb_strtolower($correct_answer)) {
                    $result['message'] = 'Chính xác!';
                    $_SESSION['status'] = 'success';
                } else {
                    $result['message'] = 'Sai đáp án!';
                    $_SESSION['status'] = 'false';
                    $_SESSION['false'] = $question_id;
                    $_SESSION['answer_wrong'] = $answer;
                    $_SESSION['correct_choice'] = $open['correct_answer'];
                }
            } else {
                $result['message'] = 'Không tìm thấy đáp án';
            }
        }
        if ($result['message'] === 'Chính xác!') {
            $_SESSION['lesson_stats']['answered']++;
            $_SESSION['lesson_stats']['correct']++;
            $_SESSION['lesson_stats']['score'] += 10;
        } else {
            $_SESSION['lesson_stats']['answered']++;
        }
        // KHÔNG tính lại duration ở đây, chỉ lấy từ client
        echo json_encode([
            'status' => $result['status'],
            'content' => $result['message'],
            'stats' => [
                'answered' => $_SESSION['lesson_stats']['answered'],
                'correct' => $_SESSION['lesson_stats']['correct'],
                'score' => $_SESSION['lesson_stats']['score'],
                'duration' => $_SESSION['lesson_stats']['duration']
            ]
        ]);
        return;
    });
?>