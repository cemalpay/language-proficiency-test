<?php
if (!defined('ABSPATH')) {
    exit;
}

// Form işleme
if (isset($_POST['submit']) && check_admin_referer('add_question', 'lpt_nonce')) {
    global $wpdb;
    $wpdb->show_errors(); // Hataları göster
    
    $language = sanitize_text_field($_POST['language']);
    $question_text = sanitize_textarea_field($_POST['question_text']);
    $level = sanitize_text_field($_POST['level']);
    $options = array_map('sanitize_text_field', $_POST['options']);
    $correct_answer = sanitize_text_field($_POST['correct_answer']);

    // Debug bilgisi
    error_log('Form Data: ' . print_r($_POST, true));

    // Options'ları harf ve değer olarak eşleştir
    $lettered_options = array();
    foreach ($options as $index => $option) {
        if (!empty($option)) {
            $letter = chr(65 + $index); // A'dan başlayarak harf ata
            $lettered_options[$letter] = $option;
        }
    }

    $options_json = json_encode($lettered_options);

    // Debug bilgisi
    error_log('Lettered Options: ' . $options_json);

    $data = array(
        'language' => $language,
        'question_text' => $question_text,
        'question_type' => 'multiple_choice',
        'level' => $level,
        'options' => $options_json,
        'correct_answer' => $correct_answer,
        'points' => 1
    );

    // Debug bilgisi
    error_log('Insert Data: ' . print_r($data, true));

    $inserted = $wpdb->insert(
        $wpdb->prefix . 'lpt_questions',
        $data,
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
    );

    if ($inserted) {
        echo '<div class="notice notice-success"><p>' . __('Question added successfully!', 'language-proficiency-test') . '</p></div>';
    } else {
        $last_error = $wpdb->last_error;
        echo '<div class="notice notice-error"><p>' . __('Error adding question: ', 'language-proficiency-test') . esc_html($last_error) . '</p></div>';
        error_log('Database Error: ' . $last_error);
    }
}

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'questions';
?>

<div class="wrap">
    <h1><?php _e('Language Proficiency Tests', 'language-proficiency-test'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=language-proficiency-test&tab=questions" class="nav-tab <?php echo $tab === 'questions' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Questions', 'language-proficiency-test'); ?>
        </a>
        <a href="?page=language-proficiency-test&tab=languages" class="nav-tab <?php echo $tab === 'languages' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Languages', 'language-proficiency-test'); ?>
        </a>
        <a href="?page=language-proficiency-test&tab=results" class="nav-tab <?php echo $tab === 'results' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Test Results', 'language-proficiency-test'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($tab === 'languages'): ?>
            <div class="languages-section">
                <div class="shortcode-info" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #ffc904;">
                    <h3 style="margin-top: 0;">Shortcode Kullanımı</h3>
                    <p>Dil testini sayfanıza eklemek için aşağıdaki shortcode'u kullanabilirsiniz:</p>
                    <code style="display: block; padding: 10px; background: #fff; border: 1px solid #ddd;">[language_test language="english" questions="20"]</code>
                    <p><strong>Parametreler:</strong></p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><code>language</code>: Dil kodu (örn: english, spanish)</li>
                        <li><code>questions</code>: Gösterilecek soru sayısı (varsayılan: 20)</li>
                    </ul>
                </div>

                <div class="language-form-container">
                    <h2><?php _e('Add New Language', 'language-proficiency-test'); ?></h2>
                    <form method="post" action="" class="language-form">
                        <?php wp_nonce_field('add_language', 'lpt_language_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="language_code">Dil Kodu</label></th>
                                <td>
                                    <input type="text" name="language_code" id="language_code" class="regular-text" required>
                                    <p class="description">Örnek: english, spanish, french</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="language_name">Dil Adı</label></th>
                                <td>
                                    <input type="text" name="language_name" id="language_name" class="regular-text" required>
                                    <p class="description">Örnek: İngilizce, İspanyolca, Fransızca</p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Dil Ekle'); ?>
                    </form>
                </div>

                <div class="languages-list">
                    <h2><?php _e('Available Languages', 'language-proficiency-test'); ?></h2>
                    <?php
                    $languages = LPT_Database::get_languages();
                    if ($languages):
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Dil Kodu</th>
                                <th>Dil Adı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($languages as $language): ?>
                            <tr>
                                <td><?php echo esc_html($language->code); ?></td>
                                <td><?php echo esc_html($language->name); ?></td>
                                <td>
                                    <a href="?page=language-proficiency-test&tab=languages&action=delete&id=<?php echo $language->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_language'); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Bu dili silmek istediğinizden emin misiniz?');">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p><?php _e('No languages found.', 'language-proficiency-test'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($tab === 'questions'): ?>
            <div class="questions-section">
                <?php
                // Silme işlemi sonrası bildirimler
                if (isset($_GET['deleted'])) {
                    if ($_GET['deleted'] === 'true') {
                        echo '<div class="notice notice-success"><p>' . __('Question deleted successfully!', 'language-proficiency-test') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>' . __('Error deleting question.', 'language-proficiency-test') . '</p></div>';
                    }
                }
                ?>
                <div class="question-form-container">
                    <h2><?php _e('Add New Question', 'language-proficiency-test'); ?></h2>
                    <form method="post" action="" class="question-form">
                        <?php wp_nonce_field('add_question', 'lpt_nonce'); ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="language"><?php _e('Language', 'language-proficiency-test'); ?></label>
                                <select name="language" id="language" required class="regular-text">
                                    <option value=""><?php _e('Select Language', 'language-proficiency-test'); ?></option>
                                    <?php
                                    $languages = LPT_Database::get_languages();
                                    foreach ($languages as $lang):
                                    ?>
                                    <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html($lang->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="level"><?php _e('Level', 'language-proficiency-test'); ?></label>
                                <select name="level" id="level" required class="regular-text">
                                    <option value=""><?php _e('Select Level', 'language-proficiency-test'); ?></option>
                                    <option value="A1">A1 - Beginner</option>
                                    <option value="A2">A2 - Elementary</option>
                                    <option value="B1">B1 - Intermediate</option>
                                    <option value="B2">B2 - Upper Intermediate</option>
                                    <option value="C1">C1 - Advanced</option>
                                    <option value="C2">C2 - Mastery</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="question_text"><?php _e('Question', 'language-proficiency-test'); ?></label>
                            <textarea name="question_text" id="question_text" class="large-text" rows="3" required 
                                    placeholder="<?php _e('Enter your question here...', 'language-proficiency-test'); ?>"></textarea>
                        </div>

                        <div class="form-group options-group">
                            <label><?php _e('Answer Options', 'language-proficiency-test'); ?></label>
                            <div id="options-container">
                                <div class="option-row">
                                    <span class="option-letter">A)</span>
                                    <input type="text" name="options[]" class="regular-text" required 
                                           placeholder="<?php _e('Enter answer option...', 'language-proficiency-test'); ?>">
                                </div>
                                <div class="option-row">
                                    <span class="option-letter">B)</span>
                                    <input type="text" name="options[]" class="regular-text" required 
                                           placeholder="<?php _e('Enter answer option...', 'language-proficiency-test'); ?>">
                                </div>
                                <div class="option-row">
                                    <span class="option-letter">C)</span>
                                    <input type="text" name="options[]" class="regular-text" required 
                                           placeholder="<?php _e('Enter answer option...', 'language-proficiency-test'); ?>">
                                </div>
                                <div class="option-row">
                                    <span class="option-letter">D)</span>
                                    <input type="text" name="options[]" class="regular-text" required 
                                           placeholder="<?php _e('Enter answer option...', 'language-proficiency-test'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="correct_answer"><?php _e('Correct Answer', 'language-proficiency-test'); ?></label>
                            <select name="correct_answer" id="correct_answer" class="regular-text" required>
                                <option value=""><?php _e('Select correct answer', 'language-proficiency-test'); ?></option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                            <p class="description"><?php _e('Select the letter of the correct answer', 'language-proficiency-test'); ?></p>
                        </div>

                        <?php submit_button(__('Add Question', 'language-proficiency-test')); ?>
                    </form>
                </div>

                <div class="questions-list">
                    <h2><?php _e('Existing Questions', 'language-proficiency-test'); ?></h2>
                    <?php
                    global $wpdb;
                    $questions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lpt_questions ORDER BY language, level");
                    if ($questions):
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="10%"><?php _e('Language', 'language-proficiency-test'); ?></th>
                                <th width="10%"><?php _e('Level', 'language-proficiency-test'); ?></th>
                                <th width="35%"><?php _e('Question', 'language-proficiency-test'); ?></th>
                                <th width="30%"><?php _e('Options', 'language-proficiency-test'); ?></th>
                                <th width="15%"><?php _e('Actions', 'language-proficiency-test'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question): 
                                $options = json_decode($question->options, true);
                            ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($question->language)); ?></td>
                                <td><?php echo esc_html($question->level); ?></td>
                                <td><?php echo esc_html($question->question_text); ?></td>
                                <td>
                                    <?php 
                                    if (is_array($options)) {
                                        echo '<ul class="options-list">';
                                        foreach ($options as $letter => $option) {
                                            $is_correct = ($letter === $question->correct_answer);
                                            echo '<li class="' . ($is_correct ? 'correct-answer' : '') . '">' . 
                                                 esc_html($letter . ') ' . $option) . 
                                                 ($is_correct ? ' ✓' : '') . 
                                                 '</li>';
                                        }
                                        echo '</ul>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?page=language-proficiency-test&action=edit&id=<?php echo $question->id; ?>" 
                                       class="button button-small">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="?page=language-proficiency-test&action=delete_question&id=<?php echo $question->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_question'); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this question?', 'language-proficiency-test'); ?>');">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p><?php _e('No questions found.', 'language-proficiency-test'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($tab === 'results'): ?>
            <div class="results-section">
                <h2><?php _e('Test Results', 'language-proficiency-test'); ?></h2>
                <?php
                global $wpdb;
                $wpdb->show_errors(true);
                
                try {
                    // Tablo varlığını kontrol et
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}lpt_test_results'") === $wpdb->prefix . 'lpt_test_results';
                    
                    if (!$table_exists) {
                        echo '<div class="notice notice-error"><p>' . __('Test results table does not exist. Please deactivate and reactivate the plugin.', 'language-proficiency-test') . '</p></div>';
                        error_log('LPT Plugin: Test results table does not exist');
                    } else {
                        $results = $wpdb->get_results("
                            SELECT r.*, u.display_name 
                            FROM {$wpdb->prefix}lpt_test_results r 
                            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
                            ORDER BY r.completed_at DESC
                        ");

                        if ($wpdb->last_error) {
                            echo '<div class="notice notice-error"><p>' . __('Database error: ', 'language-proficiency-test') . esc_html($wpdb->last_error) . '</p></div>';
                            error_log('LPT Plugin Database Error: ' . $wpdb->last_error);
                        }
                        
                        if ($results): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Student Name', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Email', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Phone', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Language', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Score', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Level', 'language-proficiency-test'); ?></th>
                                    <th><?php _e('Date', 'language-proficiency-test'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): 
                                    $answers_data = json_decode($result->answers, true);
                                    $student_info = isset($answers_data['student_info']) ? $answers_data['student_info'] : array();
                                ?>
                                <tr>
                                    <td><?php echo esc_html($student_info['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html($student_info['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html($student_info['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html(ucfirst($result->language)); ?></td>
                                    <td><?php echo esc_html($result->score); ?></td>
                                    <td><?php echo esc_html($result->level); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result->completed_at))); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p><?php _e('No test results found.', 'language-proficiency-test'); ?></p>
                        <?php endif;
                    }
                } catch (Exception $e) {
                    echo '<div class="notice notice-error"><p>' . __('Error: ', 'language-proficiency-test') . esc_html($e->getMessage()) . '</p></div>';
                    error_log('LPT Plugin Error: ' . $e->getMessage());
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.question-form-container {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.options-group {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #e5e5e5;
}

.option-letter {
    min-width: 30px;
    color: #2271b1;
    font-weight: 600;
    font-size: 14px;
}

.option-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.options-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.options-list li {
    padding: 5px 0;
    font-size: 13px;
}

.correct-answer {
    color: #28a745;
    font-weight: 600;
}

.questions-list {
    margin-top: 40px;
}

.button-small {
    padding: 0 5px !important;
    min-height: 30px;
}

.button-small .dashicons {
    font-size: 16px;
    height: 16px;
    width: 16px;
    line-height: 1.3;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Form doğrulama
    $('.question-form').on('submit', function(e) {
        var emptyOptions = false;
        
        $('input[name="options[]"]').each(function() {
            if (!$(this).val().trim()) {
                emptyOptions = true;
                return false;
            }
        });

        if (emptyOptions) {
            alert('<?php _e('Please fill in all answer options', 'language-proficiency-test'); ?>');
            e.preventDefault();
            return;
        }

        if (!$('#correct_answer').val()) {
            alert('<?php _e('Please select the correct answer', 'language-proficiency-test'); ?>');
            e.preventDefault();
            return;
        }
    });
});
</script>

<?php
// Dil ekleme işlemi
if (isset($_POST['language_code']) && isset($_POST['language_name']) && check_admin_referer('add_language', 'lpt_language_nonce')) {
    $code = sanitize_text_field($_POST['language_code']);
    $name = sanitize_text_field($_POST['language_name']);
    
    if (LPT_Database::add_language($code, $name)) {
        echo '<div class="notice notice-success"><p>Dil başarıyla eklendi.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Dil eklenirken bir hata oluştu.</p></div>';
    }
}

// Dil silme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (check_admin_referer('delete_language')) {
        $id = intval($_GET['id']);
        if (LPT_Database::delete_language($id)) {
            echo '<div class="notice notice-success"><p>Dil başarıyla silindi.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Dil silinirken bir hata oluştu.</p></div>';
        }
    }
}

// Soru silme işlemi
if (isset($_GET['action']) && $_GET['action'] === 'delete_question' && isset($_GET['id'])) {
    if (check_admin_referer('delete_question')) {
        $id = intval($_GET['id']);
        if (LPT_Database::delete_question($id)) {
            wp_redirect(add_query_arg(array('page' => 'language-proficiency-test', 'deleted' => 'true'), admin_url('admin.php')));
            exit;
        } else {
            wp_redirect(add_query_arg(array('page' => 'language-proficiency-test', 'deleted' => 'false'), admin_url('admin.php')));
            exit;
        }
    }
}
?> 