<?php
//============================================================+
// File name   : api.php
// Description : API Endpoint for React Landing Page
// Begin       : 2024-03-19
// Last Update : 2024-03-19
//
// Author: ftrhost
//
// (c) Copyright:
//               Mansaba Media
//               ftr.vercel.app
//
// License:
//    See LICENSE.TXT file for more information.
//============================================================+

require_once('../config/tce_config.php');
require_once('../../shared/code/tce_authorization.php');
require_once('../../shared/code/tce_functions_test.php');
require_once('./tce_functions_user_select.php');
require_once('../../shared/code/tce_functions_test_stats.php');
require_once('../../shared/config/tce_db_config.php');

// Suppress deprecation warnings for PHPExcel
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

// Define logs directory if not defined
if (!defined('K_PATH_LOGS')) {
    define('K_PATH_LOGS', K_PATH_MAIN.'logs/');
}

// Create the logs directory if it does not exist
if (!is_dir(K_PATH_LOGS)) {
    if (!mkdir(K_PATH_LOGS, 0777, true)) {
        error_log('Failed to create logs directory: ' . K_PATH_LOGS);
    }
}

// Set error reporting for development
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

// Set custom error handler for PHPExcel
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Only log if it's not a PHPExcel deprecated warning
    if (strpos($errfile, 'PHPExcel') === false || $errno !== E_DEPRECATED) {
        error_log("Error [$errno] $errstr on line $errline in file $errfile");
    }
    return true;
});

// Helper function to send JSON response
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to fetch all rows from a result
function F_db_fetch_all($result) {
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Helper function to validate and sanitize input
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to get group data
function F_getGroupData($group_id) {
    global $db, $l;
    $group_id = intval($group_id);
    $gd = array();
    $sql = 'SELECT * FROM '.K_TABLE_GROUPS.' WHERE group_id='.$group_id.' LIMIT 1';
    if ($r = F_db_query($sql, $db)) {
        $gd = mysqli_fetch_assoc($r);
    } else {
        // You might want to log this error more verbosely
        error_log('Database error in F_getGroupData: ' . F_db_error($db));
    }
    return $gd;
}

// Helper function to get module data
function F_getModuleData($module_id) {
    global $db;
    $module_id = intval($module_id);
    $md = array();
    $sql = 'SELECT * FROM '.K_TABLE_MODULES.' WHERE module_id='.$module_id.' LIMIT 1';
    if ($r = F_db_query($sql, $db)) {
        $md = mysqli_fetch_assoc($r);
    } else {
        error_log('Database error in F_getModuleData: ' . F_db_error($db));
    }
    return $md;
}

// Allow all origins (for development/public API)
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API Key authorization check
$api_key = isset($_GET['api_key']) ? sanitize_input($_GET['api_key']) : '';
$SECRET_API_KEY = 'GANTI DENGAN KUNCI RAHASIA YANG KUAT'; // GANTI DENGAN KUNCI RAHASIA YANG KUAT!

if ($api_key !== $SECRET_API_KEY) {
    send_json_response(['error' => 'Invalid API Key'], 401);
    exit;
}

// Helper function to generate Excel content
function generate_excel_content($test_id, $group_id, $db) {
    // Validate inputs
    if (!is_numeric($test_id) || !is_numeric($group_id)) {
        error_log('generate_excel_content: Invalid test_id or group_id: test_id='.$test_id.' group_id='.$group_id);
        return '';
    }

    // Get test and group info for filename
    $test_info = F_getTestData($test_id);
    $group_info = F_getGroupData($group_id);
    
    if (!$test_info) {
        error_log('generate_excel_content: Test not found for test_id='.$test_id);
        return '';
    }
    if (!$group_info) {
        error_log('generate_excel_content: Group not found for group_id='.$group_id);
        return '';
    }

    // Get test data with error handling
    try {
        $data = F_getAllUsersTestStat(
            $test_id, 
            $group_id, 
            0, 
            '0000-01-01 00:00:00', 
            '9999-12-31 23:59:59', 
            'user_lastname, user_firstname', 
            false, 
            1
        );
    } catch (Exception $e) {
        error_log('generate_excel_content: Error getting test stats: ' . $e->getMessage());
        return '';
    }

    if (!isset($data['testuser']) || empty($data['testuser'])) {
        error_log('generate_excel_content: No test data found for test_id='.$test_id.' group_id='.$group_id);
        return '';
    }

    error_log('generate_excel_content: Number of test users found: ' . count($data['testuser']));

    // Check if PHPExcel files exist
    $phpExcelPath = K_PATH_ADMIN_CODE.'PHPExcel/Classes/PHPExcel.php';
    if (!file_exists($phpExcelPath)) {
        error_log('generate_excel_content: PHPExcel not found at: ' . $phpExcelPath);
        return '';
    }

    try {
        require_once($phpExcelPath);
        require_once(K_PATH_ADMIN_CODE.'PHPExcel/Classes/PHPExcel/IOFactory.php');
        require_once(K_PATH_ADMIN_CODE.'PHPExcel/Classes/PHPExcel/Style/Alignment.php');
        require_once(K_PATH_ADMIN_CODE.'PHPExcel/Classes/PHPExcel/Style/Border.php');
    } catch (Exception $e) {
        error_log('generate_excel_content: Error loading PHPExcel: ' . $e->getMessage());
        return '';
    }
    
    try {
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('Hasil Tes');

        // Add Class and Subject information
        $sheet->setCellValue('A1', 'Kelas: ' . $group_info['group_name']);
        $sheet->setCellValue('A2', 'Mapel: ' . $test_info['test_name']);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);  // No
        $sheet->getColumnDimension('B')->setWidth(30); // Nama
        $sheet->getColumnDimension('C')->setWidth(30); // Mapel (re-added)
        $sheet->getColumnDimension('D')->setWidth(15); // Skor (shifted to D)

        // Style for header
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]]
        ];

        // Add header
        $headers = ['No', 'Nama Lengkap', 'Mapel', 'Skor']; // Re-added 'Mapel'
        $sheet->fromArray($headers, NULL, 'A4'); // Start at row 4 for headers
        $sheet->getStyle('A4:D4')->applyFromArray($headerStyle); // Adjusted range to D

        // Add data
        $row_num = 5; // Start data from row 5
        foreach ($data['testuser'] as $idx => $user) {
            error_log('generate_excel_content: Processing user at index ' . $idx . ', user_id: ' . (isset($user['user_id']) ? $user['user_id'] : 'N/A') . ', user_name: ' . (isset($user['user_firstname']) ? $user['user_firstname'] . ' ' . $user['user_lastname'] : 'N/A'));
            $sheet->setCellValue('A'.$row_num, $idx + 1);
            $sheet->setCellValue('B'.$row_num, $user['user_firstname'].' '.$user['user_lastname']);
            $sheet->setCellValue('C'.$row_num, $user['test']['test_name']); // Populating 'Mapel' with test name
            
            // Improved total_score handling
            $total_score = '0';
            if (isset($user['total_score'])) {
                // Convert to string first to ensure consistent handling
                $total_score = (string)$user['total_score'];
                // Remove any non-numeric characters except decimal point
                $total_score = preg_replace('/[^0-9.]/', '', $total_score);
                // Ensure we have a valid number
                if (!is_numeric($total_score)) {
                    error_log('Invalid total_score value: ' . $user['total_score'] . ' for user: ' . $user['user_firstname'] . ' ' . $user['user_lastname']);
                    $total_score = '0';
                }
            }
            
            // Convert to float for Excel cell
            $total_score = (float)$total_score;
            $sheet->setCellValue('D'.$row_num, $total_score);
            $row_num++;
        }

        // Auto-size columns
        foreach(range('A','D') as $col) { // Adjusted range to D
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save Excel to output buffer
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_start();
        $objWriter->save('php://output');
        $excelOutput = ob_get_clean();
        return $excelOutput;
    } catch (Exception $e) {
        error_log('generate_excel_content: Error generating Excel: ' . $e->getMessage());
        return '';
    }
}

// Get action from request
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';

// Validate action
$valid_actions = ['get_modules', 'get_groups', 'get_tests', 'download_excel', 'download_module_zip', 'download_group_zip'];
if (!in_array($action, $valid_actions)) {
    send_json_response(['error' => 'Invalid action. Valid actions are: ' . implode(', ', $valid_actions)], 400);
    exit;
}

// Log API requests (optional)
$log_file = K_PATH_LOGS.'api_'.date('Ymd').'.log';
$log_message = date('Y-m-d H:i:s').' - '.$_SERVER['REMOTE_ADDR'].' - '.$action."\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// Enable detailed error logging for debugging ZIP issues
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', K_PATH_LOGS.'php_error_'.date('Ymd').'.log');

// Set a custom temporary directory (created in the project root) for ZipArchive (and other PHP functions) to use.
$tmp_dir = __DIR__ . '/../../tmp';
if (!is_dir($tmp_dir)) {
    if (!@mkdir($tmp_dir, 0777, true)) {
        error_log('Failed to create temporary directory: ' . $tmp_dir . ' - Error: ' . error_get_last()['message']);
        send_json_response(['error' => 'Server configuration error: Cannot create temporary directory'], 500);
        exit;
    }
}

// Check if directory is writable
if (!is_writable($tmp_dir)) {
    error_log('Temporary directory is not writable: ' . $tmp_dir);
    send_json_response(['error' => 'Server configuration error: Temporary directory is not writable'], 500);
    exit;
}

// Set memory limit for large ZIP files
$current_memory_limit = ini_get('memory_limit');
$memory_limit_bytes = return_bytes($current_memory_limit);
if ($memory_limit_bytes < 256 * 1024 * 1024) { // Less than 256MB
    @ini_set('memory_limit', '256M');
}

// Helper function to convert memory limit string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = substr($val, 0, -1);
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Helper function to clean up temporary files
function cleanup_temp_files($pattern) {
    global $tmp_dir;
    $files = glob($tmp_dir . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file) > 3600)) { // Delete files older than 1 hour
            @unlink($file);
        }
    }
}

// Clean up old temporary files
cleanup_temp_files('*.zip');
cleanup_temp_files('*.xlsx');

try {
    switch ($action) {
        case 'get_modules':
            $sql = 'SELECT module_id, module_name FROM '.K_TABLE_MODULES.' ORDER BY module_name';
            $result = F_db_query($sql, $db);
            if (!$result) {
                throw new Exception('Database error: '.F_db_error($db));
            }
            send_json_response(F_db_fetch_all($result));
            break;

        case 'get_groups':
            $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
            if ($module_id <= 0) {
                throw new Exception('Invalid module ID');
            }

            $sql = 'SELECT DISTINCT g.group_id, g.group_name
                    FROM ' . K_TABLE_GROUPS . ' g
                    JOIN ' . K_TABLE_USERGROUP . ' ug ON g.group_id = ug.usrgrp_group_id
                    JOIN ' . K_TABLE_TEST_USER . ' tu ON ug.usrgrp_user_id = tu.testuser_user_id
                    JOIN ' . K_TABLE_TESTS . ' t ON tu.testuser_test_id = t.test_id
                    JOIN ' . K_TABLE_TEST_SUBJSET . ' tss ON t.test_id = tss.tsubset_test_id
                    JOIN ' . K_TABLE_SUBJECT_SET . ' ts ON tss.tsubset_id = ts.subjset_tsubset_id
                    JOIN ' . K_TABLE_SUBJECTS . ' s ON ts.subjset_subject_id = s.subject_id
                    WHERE s.subject_module_id = ' . $module_id . '
                    ORDER BY g.group_name';
            
            $result = F_db_query($sql, $db);
            if (!$result) {
                throw new Exception('Database error: '.F_db_error($db));
            }
            send_json_response(F_db_fetch_all($result));
            break;

        case 'get_tests':
            $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            if ($group_id <= 0) {
                throw new Exception('Invalid group ID');
            }

            $sql = 'SELECT DISTINCT t.test_id, t.test_name
                    FROM '.K_TABLE_TESTS.' t
                    JOIN '.K_TABLE_TEST_USER.' tu ON t.test_id = tu.testuser_test_id
                    JOIN '.K_TABLE_USERGROUP.' ug ON tu.testuser_user_id = ug.usrgrp_user_id
                    WHERE ug.usrgrp_group_id = '.$group_id.'
                    ORDER BY t.test_name';
            
            $result = F_db_query($sql, $db);
            if (!$result) {
                throw new Exception('Database error: '.F_db_error($db));
            }
            send_json_response(F_db_fetch_all($result));
            break;

        case 'download_excel':
            $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
            $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

            if ($test_id <= 0 || $group_id <= 0) {
                throw new Exception('Invalid test or group ID');
            }

            // Get test and group info for filename
            $test_info = F_getTestData($test_id);
            $group_info = F_getGroupData($group_id);
            
            if (!$test_info || !$group_info) {
                throw new Exception('Test or group not found');
            }

            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', 
                $test_info['test_name'].'_'.$group_info['group_name']
            );

            $excelOutput = generate_excel_content($test_id, $group_id, $db);

            // Set headers for Excel download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');

            echo $excelOutput;
            exit;

        case 'download_module_zip':
            $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
            if ($module_id <= 0) {
                error_log('download_module_zip: Invalid module ID: '.$module_id);
                throw new Exception('Invalid module ID');
            }

            $module_info = F_getModuleData($module_id);
            if (!$module_info) {
                error_log('download_module_zip: Module not found for module_id='.$module_id);
                throw new Exception('Module not found');
            }

            $module_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $module_info['module_name']);
            $zip_filename = $module_name . '_' . date('YmdHis') . '.zip';
            $tmp_zip_path = $tmp_dir . '/' . $zip_filename;

            $zip = new ZipArchive();
            if ($zip->open($tmp_zip_path, ZipArchive::CREATE) !== TRUE) {
                error_log('download_module_zip: Cannot create ZIP file at '.$tmp_zip_path);
                throw new Exception('Cannot create ZIP file');
            }
            error_log('download_module_zip: ZIP file opened successfully (at ' . $tmp_zip_path . ') for module_id=' . $module_id);

            // Get all groups for the module
            $sql_groups = 'SELECT DISTINCT g.group_id, g.group_name
                           FROM ' . K_TABLE_GROUPS . ' g
                           JOIN ' . K_TABLE_USERGROUP . ' ug ON g.group_id = ug.usrgrp_group_id
                           JOIN ' . K_TABLE_TEST_USER . ' tu ON ug.usrgrp_user_id = tu.testuser_user_id
                            JOIN ' . K_TABLE_TESTS . ' t ON tu.testuser_test_id = t.test_id
                            JOIN ' . K_TABLE_TEST_SUBJSET . ' tss ON t.test_id = tss.tsubset_test_id
                            JOIN ' . K_TABLE_SUBJECT_SET . ' ts ON tss.tsubset_id = ts.subjset_tsubset_id
                            JOIN ' . K_TABLE_SUBJECTS . ' s ON ts.subjset_subject_id = s.subject_id
                            WHERE s.subject_module_id = ' . $module_id . '
                            ORDER BY g.group_name';
            $result_groups = F_db_query($sql_groups, $db);

            if (!$result_groups) {
                error_log('download_module_zip: Database error fetching groups for module_id='.$module_id.': '.F_db_error($db));
            } else if (mysqli_num_rows($result_groups) === 0) {
                error_log('download_module_zip: No groups found for module_id='.$module_id);
            }
            
            if ($result_groups) {
                while ($group = mysqli_fetch_assoc($result_groups)) {
                    $group_name_sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $group['group_name']);
                    error_log('download_module_zip: Processing group: '.$group['group_name'].' (ID: '.$group['group_id'].')');
                    
                    // Get tests for each group
                    $sql_tests = 'SELECT DISTINCT t.test_id, t.test_name
                                  FROM '.K_TABLE_TESTS.' t
                                  JOIN '.K_TABLE_TEST_USER.' tu ON t.test_id = tu.testuser_test_id
                                  JOIN '.K_TABLE_USERGROUP.' ug ON tu.testuser_user_id = ug.usrgrp_user_id
                                  WHERE ug.usrgrp_group_id = '.$group['group_id'].'
                                  ORDER BY t.test_name';
                    $result_tests = F_db_query($sql_tests, $db);

                    if (!$result_tests) {
                        error_log('download_module_zip: Database error fetching tests for group_id='.$group['group_id'].': '.F_db_error($db));
                    } else if (mysqli_num_rows($result_tests) === 0) {
                        error_log('download_module_zip: No tests found for group_id='.$group['group_id']);
                    }

                    if ($result_tests) {
                        while ($test = mysqli_fetch_assoc($result_tests)) {
                            $test_name_sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $test['test_name']);
                            $excel_filename = $test_name_sanitized . '.xlsx';
                            $zip_path = $module_name . '/' . $group_name_sanitized . '/' . $excel_filename;
                            error_log('download_module_zip: Attempting to generate Excel for test: '.$test['test_name'].' (ID: '.$test['test_id'].') in path: '.$zip_path);

                            $excel_content = generate_excel_content($test['test_id'], $group['group_id'], $db);
                            if (!empty($excel_content)) {
                                if ($zip->addFromString($zip_path, $excel_content)) {
                                    error_log('download_module_zip: Successfully added '.$zip_path.' to ZIP. Size: '.strlen($excel_content).' bytes.');
                                } else {
                                    error_log('download_module_zip: Failed to add '.$zip_path.' to ZIP.');
                                }
                            } else {
                                error_log('download_module_zip: generate_excel_content returned empty for test_id='.$test['test_id'].' group_id='.$group['group_id']);
                            }
                        }
                    }
                }
            }

            $zip_status = $zip->close();
            if ($zip_status !== TRUE) {
                error_log('download_module_zip: Failed to close ZIP archive (at ' . $tmp_zip_path . '). Status: ' . $zip_status);
                 throw new Exception('Failed to finalize ZIP archive.');
            }
            error_log('download_module_zip: ZIP archive closed (at ' . $tmp_zip_path . ') successfully.');

            // Read the (temporary) ZIP file and output it (then clean up) so that the client receives the download.
            if (file_exists($tmp_zip_path)) {
                 header('Content-Type: application/zip');
                 header('Content-Disposition: attachment; filename="' . $module_name . '.zip"');
                 header('Content-Transfer-Encoding: binary');
                 header('Cache-Control: no-cache, no-store, must-revalidate');
                 header('Pragma: no-cache');
                 header('Expires: 0');
                 readfile($tmp_zip_path);
                 unlink($tmp_zip_path); // Clean up (delete) the temporary file
                 exit;
            } else {
                 error_log('download_module_zip: (Temporary) ZIP file (at ' . $tmp_zip_path . ') does not exist after close.');
                 throw new Exception("Temporary ZIP file not found.");
            }

        case 'download_group_zip':
            $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            if ($group_id <= 0) {
                error_log('download_group_zip: Invalid group ID: '.$group_id);
                throw new Exception('Invalid group ID');
            }

            $group_info = F_getGroupData($group_id);
            if (!$group_info) {
                error_log('download_group_zip: Group not found for group_id='.$group_id);
                throw new Exception('Group not found');
            }

            $group_name_sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $group_info['group_name']);
            $zip_filename = $group_name_sanitized . '_' . date('YmdHis') . '.zip';
            $tmp_zip_path = $tmp_dir . '/' . $zip_filename;

            $zip = new ZipArchive();
            if ($zip->open($tmp_zip_path, ZipArchive::CREATE) !== TRUE) {
                error_log('download_group_zip: Cannot create ZIP file at '.$tmp_zip_path);
                throw new Exception('Cannot create ZIP file');
            }
            error_log('download_group_zip: ZIP file opened successfully for group_id='.$group_id);

            // Get all tests for the specified group
            $sql_tests_group = 'SELECT DISTINCT t.test_id, t.test_name
                                FROM '.K_TABLE_TESTS.' t
                                JOIN '.K_TABLE_TEST_USER.' tu ON t.test_id = tu.testuser_test_id
                                JOIN '.K_TABLE_USERGROUP.' ug ON tu.testuser_user_id = ug.usrgrp_user_id
                                WHERE ug.usrgrp_group_id = '.$group_id.'
                                ORDER BY t.test_name';
            $result_tests_group = F_db_query($sql_tests_group, $db);

            if (!$result_tests_group) {
                error_log('download_group_zip: Database error fetching tests for group_id='.$group_id.': '.F_db_error($db));
            } else if (mysqli_num_rows($result_tests_group) === 0) {
                error_log('download_group_zip: No tests found for group_id='.$group_id);
            }

            if ($result_tests_group) {
                while ($test = mysqli_fetch_assoc($result_tests_group)) {
                    $test_name_sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $test['test_name']);
                    $excel_filename = $test_name_sanitized . '.xlsx';
                    // Simplified path: just group_name/test_name.xlsx
                    $zip_path = $group_name_sanitized . '/' . $excel_filename;
                    error_log('download_group_zip: Attempting to generate Excel for test: '.$test['test_name'].' (ID: '.$test['test_id'].') in path: '.$zip_path);

                    $excel_content = generate_excel_content($test['test_id'], $group_id, $db);
                    if (!empty($excel_content)) {
                        if ($zip->addFromString($zip_path, $excel_content)) {
                            error_log('download_group_zip: Successfully added '.$zip_path.' to ZIP. Size: '.strlen($excel_content).' bytes.');
                        } else {
                            error_log('download_group_zip: Failed to add '.$zip_path.' to ZIP.');
                        }
                    } else {
                        error_log('download_group_zip: generate_excel_content returned empty for test_id='.$test['test_id'].' group_id='.$group_id);
                    }
                }
            }

            $zip_status = $zip->close();
            if ($zip_status !== TRUE) {
                error_log('download_group_zip: Failed to close ZIP archive (at ' . $tmp_zip_path . '). Status: ' . $zip_status);
                throw new Exception('Failed to finalize ZIP archive.');
            }
            error_log('download_group_zip: ZIP archive closed (at ' . $tmp_zip_path . ') successfully.');

            // Read the temporary ZIP file and output it (then clean up)
            if (file_exists($tmp_zip_path)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $group_name_sanitized . '.zip"');
                header('Content-Transfer-Encoding: binary');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                readfile($tmp_zip_path);
                unlink($tmp_zip_path); // Clean up
                exit;
            } else {
                error_log('download_group_zip: Temporary ZIP file (at ' . $tmp_zip_path . ') does not exist after close.');
                throw new Exception("Temporary ZIP file not found.");
            }

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Log error
    $error_log = K_PATH_LOGS.'api_error_'.date('Ymd').'.log';
    $error_message = date('Y-m-d H:i:s').' - '.$_SERVER['REMOTE_ADDR'].' - '.$e->getMessage()."\n";
    file_put_contents($error_log, $error_message, FILE_APPEND);

    // Send error response
    send_json_response(['error' => $e->getMessage()], 400);
}

//============================================================+
// END OF FILE
//============================================================+ 
