<?php

// Check if it is a GET request for choices AJAX loading
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_choices'])) {
    $question_id = sani($_GET['get_choices']);
    
    // Fetch choices
    $result = querySecure($con, "SELECT * FROM test_question_choices WHERE question_id = ? ORDER BY created_at ASC", [$question_id], 's');
    $choices = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $choices[] = $row;
        }
    }
    
    // Fetch medias
    $mediasRes = querySecure($con, "SELECT * FROM test_question_medias WHERE question_id = ? ORDER BY created_at ASC", [$question_id], 's');
    $medias = [];
    if ($mediasRes) {
        while ($row = mysqli_fetch_assoc($mediasRes)) {
            $medias[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'choices' => $choices,
        'medias' => $medias
    ]);
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $questions = $_POST['questions'] ?? [];
        $successCount = 0;
        $failCount = 0;
        $lastTestId = '';

        if (!empty($questions)) {
            include_once '../functions/upload_file.php';
            foreach ($questions as $qIndex => $qData) {
                $id = generate_uuid();
                $test_id = sani($qData['test_id'] ?? '');
                $question = sani($qData['question'] ?? '');
                $question_type = sani($qData['question_type'] ?? '');
                $questions_material = sani($qData['questions_material'] ?? '');

                if (empty($test_id) || empty($question) || empty($question_type)) {
                    $failCount++;
                    continue;
                }

                $lastTestId = $test_id;

                $query = "INSERT INTO test_questions (id, test_id, question, question_type, questions_material) VALUES (?, ?, ?, ?, ?)";
                $params = [$id, $test_id, $question, $question_type, $questions_material];
                $types = 'sssss';
                $insertResult = executeSecure($con, $query, $params, $types);

                if ($insertResult) {
                    $successCount++;

                    // Save choices if type is Multiple Choice
                    if ($question_type === 'Multiple Choice' && isset($qData['choices'])) {
                        $choices = $qData['choices'];
                        $trueIndex = isset($qData['choice_true_index']) ? intval($qData['choice_true_index']) : 0;
                        foreach ($choices as $i => $choice) {
                            $cText = sani($choice['text'] ?? '');
                            if (empty($cText)) continue;
                            $cId = generate_uuid();
                            $cTrue = ($i === $trueIndex) ? 'true' : 'false';
                            $cPoint = sani($choice['point'] ?? '0');
                            
                            $mediaPath = null;
                            $fileInputName = 'questions_choice_media_files_' . $qIndex . '_' . $i;
                            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                                $uploadRes = uploadFile($_FILES[$fileInputName], '../assets/test_medias/');
                                if ($uploadRes && $uploadRes['success']) {
                                    $mediaPath = preg_replace('/^\.\.\//', '', $uploadRes['file_path']);
                                }
                            }
                            
                            executeSecure($con, 
                                "INSERT INTO test_question_choices (id, question_id, choice_text, choice_true, point, media_file) VALUES (?, ?, ?, ?, ?, ?)",
                                [$cId, $id, $cText, $cTrue, $cPoint, $mediaPath],
                                'ssssss'
                            );
                        }
                    }

                    // Process media uploads
                    $fileInputName = 'questions_media_files_' . $qIndex;
                    if (isset($_FILES[$fileInputName]) && is_array($_FILES[$fileInputName]['name'])) {
                        $files = $_FILES[$fileInputName];
                        $names = $qData['media_names'] ?? [];
                        
                        for ($i = 0; $i < count($files['name']); $i++) {
                            if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            
                            $fileObj = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];
                            
                            $uploadRes = uploadFile($fileObj, '../assets/test_medias/');
                            if ($uploadRes && $uploadRes['success']) {
                                $mId = generate_uuid();
                                $dbPath = preg_replace('/^\.\.\//', '', $uploadRes['file_path']);
                                $mName = sani($names[$i] ?? '');
                                if (empty($mName)) {
                                    $mName = pathinfo($files['name'][$i], PATHINFO_FILENAME);
                                }
                                $mExt = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                                
                                executeSecure($con,
                                    "INSERT INTO test_question_medias (id, question_id, media_name, path, media_extendsion) VALUES (?, ?, ?, ?, ?)",
                                    [$mId, $id, $mName, $dbPath, $mExt],
                                    'sssss'
                                );
                            }
                        }
                    }
                } else {
                    $failCount++;
                }
            }
        }

        $redirectUrl = '../?hal=test_test-question';
        if (!empty($lastTestId)) {
            $redirectUrl .= '&id=' . $lastTestId;
        } elseif (!empty($_GET['id'])) {
            $redirectUrl .= '&id=' . sani($_GET['id']);
        }

        if ($successCount > 0) {
            createLog($con, $_SESSION['admin']['email'], "Successful question addition bulk ($successCount successful, $failCount failed)");
            redirectWithMessage($redirectUrl, "$successCount data berhasil ditambahkan!" . ($failCount > 0 ? " ($failCount gagal)" : ""), 'success');
        } else {
            redirectWithMessage($redirectUrl, 'Terjadi kesalahan saat menambahkan data.', 'error');
        }
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $test_id = sani($_POST['test_id']);
        $question = sani($_POST['question']);
        $question_type = sani($_POST['question_type']);
        $questions_material = sani($_POST['questions_material']);
        
        $query = "UPDATE test_questions SET test_id = ?, question = ?, question_type = ?, questions_material = ? WHERE id = ?";
        $params = [$test_id, $question, $question_type, $questions_material, $id];
        $types = 'sssss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            // Save choices if type is Multiple Choice
            if ($question_type === 'Multiple Choice' && isset($_POST['choices'])) {
                $choices = $_POST['choices'];
                $trueIndex = isset($_POST['choice_true_index']) ? intval($_POST['choice_true_index']) : 0;
                foreach ($choices as $i => $choice) {
                    $cText = sani($choice['text'] ?? '');
                    $cId = sani($choice['id'] ?? '');
                    $cPoint = sani($choice['point'] ?? '0');
                    $cTrue = ($i === $trueIndex) ? 'true' : 'false';
                    
                    if (!empty($cId)) {
                        if (empty($cText)) {
                            // Delete choice if text is cleared
                            executeSecure($con, "DELETE FROM test_question_choices WHERE id = ?", [$cId], 's');
                        } else {
                            // Update choice
                            executeSecure($con, 
                                "UPDATE test_question_choices SET choice_text = ?, choice_true = ?, point = ? WHERE id = ?",
                                [$cText, $cTrue, $cPoint, $cId],
                                'ssss'
                            );
                            
                            // Check if remove media is checked
                            if (isset($choice['remove_media']) && $choice['remove_media'] === '1') {
                                $oldRes = querySecure($con, "SELECT media_file FROM test_question_choices WHERE id = ?", [$cId], 's');
                                if ($oldRes && $oldRow = mysqli_fetch_assoc($oldRes)) {
                                    if (!empty($oldRow['media_file']) && file_exists('../' . $oldRow['media_file'])) {
                                        @unlink('../' . $oldRow['media_file']);
                                    }
                                }
                                executeSecure($con, "UPDATE test_question_choices SET media_file = NULL WHERE id = ?", [$cId], 's');
                            }
                            
                            // Check for new upload
                            $fileInputName = "edit_choice_media_files_{$i}";
                            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                                include_once '../functions/upload_file.php';
                                $oldRes = querySecure($con, "SELECT media_file FROM test_question_choices WHERE id = ?", [$cId], 's');
                                if ($oldRes && $oldRow = mysqli_fetch_assoc($oldRes)) {
                                    if (!empty($oldRow['media_file']) && file_exists('../' . $oldRow['media_file'])) {
                                        @unlink('../' . $oldRow['media_file']);
                                    }
                                }
                                
                                $uploadRes = uploadFile($_FILES[$fileInputName], '../assets/test_medias/');
                                if ($uploadRes && $uploadRes['success']) {
                                    $mediaPath = preg_replace('/^\.\.\//', '', $uploadRes['file_path']);
                                    executeSecure($con, "UPDATE test_question_choices SET media_file = ? WHERE id = ?", [$mediaPath, $cId], 'ss');
                                }
                            }
                        }
                    } else {
                        if (!empty($cText)) {
                            // Insert new choice
                            $newChoiceId = generate_uuid();
                            
                            $mediaPath = null;
                            $fileInputName = "edit_choice_media_files_{$i}";
                            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                                include_once '../functions/upload_file.php';
                                $uploadRes = uploadFile($_FILES[$fileInputName], '../assets/test_medias/');
                                if ($uploadRes && $uploadRes['success']) {
                                    $mediaPath = preg_replace('/^\.\.\//', '', $uploadRes['file_path']);
                                }
                            }
                            
                            executeSecure($con,
                                "INSERT INTO test_question_choices (id, question_id, choice_text, choice_true, point, media_file) VALUES (?, ?, ?, ?, ?, ?)",
                                [$newChoiceId, $id, $cText, $cTrue, $cPoint, $mediaPath],
                                'ssssss'
                            );
                        }
                    }
                }
            }

            // Delete marked media files
            if (isset($_POST['deleted_media_ids']) && is_array($_POST['deleted_media_ids'])) {
                foreach ($_POST['deleted_media_ids'] as $dmId) {
                    $dmId = sani($dmId);
                    // Fetch path first to delete file on disk
                    $mediaQuery = querySecure($con, "SELECT path FROM test_question_medias WHERE id = ?", [$dmId], 's');
                    if ($mediaQuery && $mRow = mysqli_fetch_assoc($mediaQuery)) {
                        $diskPath = '../' . $mRow['path'];
                        if (file_exists($diskPath)) {
                            @unlink($diskPath);
                        }
                    }
                    executeSecure($con, "DELETE FROM test_question_medias WHERE id = ?", [$dmId], 's');
                }
            }

            // Update existing media display names
            if (isset($_POST['existing_media_names']) && is_array($_POST['existing_media_names'])) {
                foreach ($_POST['existing_media_names'] as $emId => $emName) {
                    $emId = sani($emId);
                    $emName = sani($emName);
                    if (!empty($emName)) {
                        executeSecure($con, "UPDATE test_question_medias SET media_name = ? WHERE id = ?", [$emName, $emId], 'ss');
                    }
                }
            }

            // Process new media uploads
            if (isset($_FILES['media_files']) && is_array($_FILES['media_files']['name'])) {
                include_once '../functions/upload_file.php';
                
                $files = $_FILES['media_files'];
                $names = $_POST['media_names'] ?? [];
                
                for ($i = 0; $i < count($files['name']); $i++) {
                    if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    
                    $fileObj = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    
                    $uploadRes = uploadFile($fileObj, '../assets/test_medias/');
                    if ($uploadRes && $uploadRes['success']) {
                        $mId = generate_uuid();
                        $dbPath = preg_replace('/^\.\.\//', '', $uploadRes['file_path']);
                        $mName = sani($names[$i] ?? '');
                        if (empty($mName)) {
                            $mName = pathinfo($files['name'][$i], PATHINFO_FILENAME);
                        }
                        $mExt = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                        
                        executeSecure($con,
                            "INSERT INTO test_question_medias (id, question_id, media_name, path, media_extendsion) VALUES (?, ?, ?, ?, ?)",
                            [$mId, $id, $mName, $dbPath, $mExt],
                            'sssss'
                        );
                    }
                }
            }

            $redirectUrl = '../?hal=test_test-question';
            if (!empty($test_id)) {
                $redirectUrl .= '&id=' . $test_id;
            }
            createLog($con, $_SESSION['admin']['email'], 'Successful question update ' . $question);
            redirectWithMessage($redirectUrl, 'Data berhasil diperbarui!', 'success');
        } else {
            $redirectUrl = '../?hal=test_test-question';
            if (!empty($test_id)) {
                $redirectUrl .= '&id=' . $test_id;
            }
            redirectWithMessage($redirectUrl, 'Terjadi kesalahan saat memperbarui data.', 'error');
        }
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Get test_id and question text for redirect & logging before deleting
    $resultQuestion = querySecure($con, "SELECT test_id, question FROM test_questions WHERE id = ?", [$id], 's');
    $questionRow = mysqli_fetch_assoc($resultQuestion);
    $test_id = $questionRow ? $questionRow['test_id'] : '';
    $questionText = $questionRow ? $questionRow['question'] : $id;

    // Delete files for choices
    $choiceQuery = querySecure($con, "SELECT media_file FROM test_question_choices WHERE question_id = ?", [$id], 's');
    if ($choiceQuery) {
        while ($cRow = mysqli_fetch_assoc($choiceQuery)) {
            if (!empty($cRow['media_file'])) {
                $diskPath = '../' . $cRow['media_file'];
                if (file_exists($diskPath)) {
                    @unlink($diskPath);
                }
            }
        }
    }
    
    // Hapus data pilihan (choices) terkait
    executeSecure($con, "DELETE FROM test_question_choices WHERE question_id = ?", [$id], 's');

    // Hapus data media (files on disk & DB records) terkait
    $mediaQuery = querySecure($con, "SELECT path FROM test_question_medias WHERE question_id = ?", [$id], 's');
    if ($mediaQuery) {
        while ($mRow = mysqli_fetch_assoc($mediaQuery)) {
            $diskPath = '../' . $mRow['path'];
            if (file_exists($diskPath)) {
                @unlink($diskPath);
            }
        }
    }
    executeSecure($con, "DELETE FROM test_question_medias WHERE question_id = ?", [$id], 's');

    // Hapus data soal
    $deleteResult = executeSecure($con, "DELETE FROM test_questions WHERE id = ?", [$id], 's');

    $redirectUrl = '../?hal=test_test-question';
    if (!empty($test_id)) {
        $redirectUrl .= '&id=' . $test_id;
    }

    if ($deleteResult) {
        createLog($con, $_SESSION['admin']['email'], 'Successful question deletion ' . $questionText);
        redirectWithMessage($redirectUrl, 'Data berhasil dihapus!', 'success');
    } else {
        redirectWithMessage($redirectUrl, 'Terjadi kesalahan saat menghapus data.', 'error');
    }
} else {
    // If accessed directly, redirect to homepage
    redirectWithMessage('../../index.php', 'Akses tidak valid.', 'error');
}
