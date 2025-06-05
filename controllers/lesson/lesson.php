<?php
$app->router("/lesson", 'GET', function($vars) use ($app) {
        //Lấy tất cả id câu hỏi
        $ids = $app->select("questions", ["question_id"], ["lesson_id" => 9]);
        if (!$ids || count($ids) === 0) {
            echo json_encode(['error' => 'No questions found']);
            return;
        }
        // Random 1 id trong số đó
        $randomIndex = array_rand($ids);
        $id = $ids[$randomIndex]['question_id'];
        //$id = intval($_GET['id'] ?? 3);
        // Lấy thông tin câu hỏi
        $question = $app->get("questions", "*", ["question_id" => $id]);
        if (!$question) {
            echo json_encode(['error' => 'Question not found']);
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
            "hints"         => $hintArr
        ];

        // Nếu là trắc nghiệm
        if ($type_name === 'multiple_choice') {
            $choices = $app->select("choices", ["choice_id", "choice_text"], [
                "question_id" => $id,
                "ORDER" => ["display_order" => "ASC"]
            ]);
            //$vars['choice_id'] = $choices['choice_id'];
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
        echo $app->render('templates/lessons/lesson.html', $vars);
    });
    $app->router("/lesson", 'POST', function($vars) use ($app) {
        $app->header([
            'Content-Type' => 'application/json',
        ]);

        $question_id = intval($_POST['question_id'] ?? 0);
        $answer = trim($_POST['answer'] ?? '');

        if (!$question_id || $answer === '') {
            echo json_encode(['status' => 'error', 'content' => 'Không được để trống'.$question_id.' '. $answer]);
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
            'status' => 'error',
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
                $result['status'] = 'success';
                $_SESSION['status'] = 'success';
            } else {
                $result['message'] = 'Sai đáp án!'.$question_id.' '. $answer;
            }
        } else {
            // Tự luận hoặc điền đáp án
            $open = $app->get("open_answers", "*", ["question_id" => $question_id]);
            if ($open) {
                // So sánh đáp án (có thể cần xử lý nâng cao hơn)
                $correct_answer = trim($open['correct_answer']);
                if (mb_strtolower($answer) == mb_strtolower($correct_answer)) {
                    $result['message'] = 'Chính xác!';
                    $result['status'] = 'success';
                    $_SESSION['status'] = 'success';
                } else {
                    $result['message'] = 'Sai đáp án!';
                }
            } else {
                $result['message'] = 'Không tìm thấy đáp án';
            }
        }
        echo json_encode(['status' => $result['status'], 'content' => $result['message']]);
        return;
    });
?>