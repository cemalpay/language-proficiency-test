<?php
class LPT_Test_Manager {
    public function init() {
        add_shortcode('language_test', array($this, 'render_test'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
        add_action('wp_ajax_submit_language_test', array($this, 'handle_test_submission'));
        add_action('wp_ajax_nopriv_submit_language_test', array($this, 'handle_test_submission'));

        // Debug için gelişmiş log
        add_action('wp_footer', function() {
            ?>
            <script>
            console.log('PHP debug: Script yükleniyor...');
            document.addEventListener('DOMContentLoaded', function() {
                console.log('PHP debug: DOM yüklendi');
                
                // Önemli elementleri kontrol et
                var startButton = document.getElementById('start-test-btn');
                console.log('PHP debug: Start button:', startButton);
                
                var studentInfoForm = document.getElementById('lpt-student-info');
                console.log('PHP debug: Student info form:', studentInfoForm);
                
                var testQuestions = document.getElementById('lpt-test-questions');
                console.log('PHP debug: Test questions container:', testQuestions);
                
                var questions = document.querySelectorAll('.lpt-question');
                console.log('PHP debug: Question count:', questions.length);
                
                // JavaScript hatalarını yakala ve logla
                window.addEventListener('error', function(e) {
                    console.error('JavaScript Error:', e.message, 'at', e.filename, 'line', e.lineno);
                });
            });
            </script>
            <?php
        }, 999);
    }

    public function enqueue_scripts() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'language_test')) {
            wp_enqueue_style('lpt-style', LPT_PLUGIN_URL . 'assets/css/style.css', array(), LPT_VERSION);
            
            // jQuery'yi yükle
            wp_enqueue_script('jquery');
            
            // Confetti efekti için script yükle
            wp_enqueue_script('confetti-script', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js', array(), '1.5.1', true);
            
            // Ana script'i yükle
            wp_enqueue_script('lpt-script', LPT_PLUGIN_URL . 'assets/js/script.js', array('jquery', 'confetti-script'), time(), true);
            
            // AJAX için gerekli değişkenleri ekle
            wp_localize_script('lpt-script', 'lptAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lpt-nonce')
            ));
        }
    }

    public function render_test($atts) {
        $atts = shortcode_atts(array(
            'language' => 'english',
            'questions' => 20
        ), $atts);

        $questions = LPT_Database::get_questions($atts['language'], $atts['questions']);
        
        if (empty($questions)) {
            return '<p>' . __('No questions available for this language.', 'language-proficiency-test') . '</p>';
        }

        ob_start();
        ?>
        <div class="lpt-test-container" data-language="<?php echo esc_attr($atts['language']); ?>" style="max-width: 900px; margin: 3em auto; padding: 2.5em; background: linear-gradient(145deg, #ffffff, #f8fafc); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12); border-radius: 24px; position: relative; z-index: 1; overflow: hidden;">
            <!-- Dekoratif arka plan elementleri -->
            <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: linear-gradient(45deg, rgba(255, 201, 4, 0.1), rgba(255, 210, 52, 0.05)); border-radius: 50%; z-index: -1;"></div>
            <div style="position: absolute; bottom: -50px; left: -50px; width: 200px; height: 200px; background: linear-gradient(45deg, rgba(255, 201, 4, 0.08), rgba(255, 210, 52, 0.03)); border-radius: 50%; z-index: -1;"></div>
            
            <!-- Öğrenci Bilgi Formu -->
            <div id="lpt-student-info" class="lpt-section active" style="display: block; position: relative;">
                <div style="text-align: center; margin-bottom: 40px;">
                    <h2 style="font-size: 36px; color: #1a1a1a; margin-bottom: 16px; font-weight: 800; line-height: 1.3; background: linear-gradient(45deg, #1a1a1a, #2c3e50); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Seviye Tespit Sınavı
                    </h2>
                    <p style="font-size: 18px; color: #64748b; line-height: 1.6; max-width: 600px; margin: 0 auto;">
                        Dil seviyenizi belirlemek için lütfen aşağıdaki bilgileri doldurun. Test yaklaşık <strong style="color: #1a1a1a;">15-20 dakika</strong> sürecektir.
                    </p>
                </div>

                <div id="lpt-info-form" class="lpt-form" style="background:#ffffff;padding:40px;border-radius:20px;border:1px solid rgba(0,0,0,0.05);width:100%;display:block;box-shadow:0 8px 24px rgba(0,0,0,0.04);">
                    <!-- İsim ve E-posta Satırı -->
                    <div class="form-row" style="display:flex;gap:24px;margin-bottom:24px;">
                        <div class="form-group" style="flex:1;position:relative;">
                            <label for="student_name" class="form-label" style="display:block;margin-bottom:10px;font-weight:600;color:#1a1a1a;font-size:15px;line-height:1.4;">
                                <span style="display:flex;align-items:center;gap:8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    İsim Soyisim *
                                </span>
                            </label>
                            <input type="text" id="student_name" name="student_name" required style="width:100%;padding:16px;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;transition:all 0.2s ease;background-color:white;color:#1a1a1a;font-weight:500;">
                        </div>
                        <div class="form-group" style="flex:1;position:relative;">
                            <label for="student_email" class="form-label" style="display:block;margin-bottom:10px;font-weight:600;color:#1a1a1a;font-size:15px;line-height:1.4;">
                                <span style="display:flex;align-items:center;gap:8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 6L12 13L2 6" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    E-posta *
                                </span>
                            </label>
                            <input type="email" id="student_email" name="student_email" required style="width:100%;padding:16px;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;transition:all 0.2s ease;background-color:white;color:#1a1a1a;font-weight:500;">
                        </div>
                    </div>
                    
                    <!-- Telefon ve Öğrenme Amacı Satırı -->
                    <div class="form-row" style="display:flex;gap:24px;margin-bottom:24px;">
                        <div class="form-group" style="flex:1;position:relative;">
                            <label for="student_phone" class="form-label" style="display:block;margin-bottom:10px;font-weight:600;color:#1a1a1a;font-size:15px;line-height:1.4;">
                                <span style="display:flex;align-items:center;gap:8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 16.92V19.92C22 20.4704 21.7893 20.9987 21.4142 21.3738C21.0391 21.7489 20.5108 21.9596 19.96 21.96C16.4289 21.96 13.0149 20.8557 10.0675 18.8147C7.32778 16.9508 5.06056 14.6836 3.19666 11.9439C1.14762 8.98805 0.0432162 5.56406 0.0400055 2.02297C0.0400055 1.47456 0.249969 0.947305 0.623966 0.572308C0.997964 0.197311 1.47322 0.00634766 1.97 0.00634766H4.97C5.90556 -0.0267773 6.72445 0.610468 6.87 1.52297C7.02883 2.5929 7.29849 3.64232 7.67 4.65297C7.93264 5.35195 7.81039 6.14122 7.34 6.65297L6.09 7.90297C7.83205 10.7561 10.1739 13.098 13.027 14.84L14.277 13.59C14.7888 13.1196 15.578 13.098 16.277 13.26C17.2877 13.6315 18.3371 13.9012 19.407 14.06C20.3383 14.2076 21.0264 15.0509 20.97 16L22 16.92Z" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Telefon Numarası *
                                </span>
                            </label>
                            <input type="tel" id="student_phone" name="student_phone" required style="width:100%;padding:16px;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;transition:all 0.2s ease;background-color:white;color:#1a1a1a;font-weight:500;">
                        </div>
                        <div class="form-group" style="flex:1;position:relative;">
                            <label for="learning_purpose" class="form-label purpose-label" style="display:block;margin-bottom:10px;font-weight:600;color:#1a1a1a;font-size:15px;line-height:1.4;">
                                <span style="display:flex;align-items:center;gap:8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.579 9.96512 20.258 9.77251 19.9887C9.5799 19.7194 9.31074 19.5143 9 19.4C8.69838 19.2669 8.36381 19.2272 8.03941 19.286C7.71502 19.3448 7.41568 19.4995 7.18 19.73L7.12 19.79C6.93425 19.976 6.71368 20.1235 6.47088 20.2241C6.22808 20.3248 5.96783 20.3766 5.705 20.3766C5.44217 20.3766 5.18192 20.3248 4.93912 20.2241C4.69632 20.1235 4.47575 19.976 4.29 19.79C4.10405 19.6043 3.95653 19.3837 3.85588 19.1409C3.75523 18.8981 3.70343 18.6378 3.70343 18.375C3.70343 18.1122 3.75523 17.8519 3.85588 17.6091C3.95653 17.3663 4.10405 17.1457 4.29 16.96L4.35 16.9C4.58054 16.6643 4.73519 16.365 4.794 16.0406C4.85282 15.7162 4.81312 15.3816 4.68 15.08C4.55324 14.7842 4.34276 14.532 4.07447 14.3543C3.80618 14.1766 3.49179 14.0813 3.17 14.08H3C2.46957 14.08 1.96086 13.8693 1.58579 13.4942C1.21071 13.1191 1 12.6104 1 12.08C1 11.5496 1.21071 11.0409 1.58579 10.6658C1.96086 10.2907 2.46957 10.08 3 10.08H3.09C3.42099 10.0723 3.742 9.96512 4.0113 9.77251C4.28059 9.5799 4.48572 9.31074 4.6 9C4.73312 8.69838 4.77282 8.36381 4.714 8.03941C4.65519 7.71502 4.50054 7.41568 4.27 7.18L4.21 7.12C4.02405 6.93425 3.87653 6.71368 3.77588 6.47088C3.67523 6.22808 3.62343 5.96783 3.62343 5.705C3.62343 5.44217 3.67523 5.18192 3.77588 4.93912C3.87653 4.69632 4.02405 4.47575 4.21 4.29C4.39575 4.10405 4.61632 3.95653 4.85912 3.85588C5.10192 3.75523 5.36217 3.70343 5.625 3.70343C5.88783 3.70343 6.14808 3.75523 6.39088 3.85588C6.63368 3.95653 6.85425 4.10405 7.04 4.29L7.1 4.35C7.33568 4.58054 7.63502 4.73519 7.95941 4.794C8.28381 4.85282 8.61838 4.81312 8.92 4.68H9C9.29577 4.55324 9.54802 4.34276 9.72569 4.07447C9.90337 3.80618 9.99872 3.49179 10 3.17V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="#1a1a1a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <?php 
                                    $language_names = array(
                                        'english' => 'İngilizce',
                                        'spanish' => 'İspanyolca',
                                        'french' => 'Fransızca',
                                        'german' => 'Almanca',
                                        'italian' => 'İtalyanca'
                                    );
                                    $language_name = isset($language_names[$atts['language']]) ? $language_names[$atts['language']] : ucfirst($atts['language']);
                                    echo $language_name . ' Dilini Öğrenme Amacınız *';
                                    ?>
                                </span>
                            </label>
                            <select name="learning_purpose" id="learning_purpose" required class="purpose-select" style="width:100%;padding:16px;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;background-color:white;height:auto;appearance:auto;-webkit-appearance:auto;-moz-appearance:auto;cursor:pointer;transition:all 0.2s ease;color:#1a1a1a;font-weight:500;">
                                <option value="">Lütfen seçiniz</option>
                                <option value="academic">Akademik amaçla öğrenmek istiyorum</option>
                                <option value="travel">Seyahat amacıyla öğrenmek istiyorum</option>
                                <option value="business">İş amacıyla öğrenmek istiyorum</option>
                                <option value="personal">Kişisel gelişim amacıyla öğrenmek istiyorum</option>
                                <option value="other">Diğer amaçlarla öğrenmek istiyorum</option>
                            </select>
                        </div>
                    </div>

                    <!-- KVKK Onayı -->
                    <div class="form-group kvkk-row" style="margin:35px 0;padding:25px;background:linear-gradient(to right, rgba(255, 201, 4, 0.05), rgba(255, 210, 52, 0.02));border-radius:16px;border-left:4px solid #ffc904;">
                        <label class="kvkk-label" id="kvkk-label-important" style="display:flex;align-items:flex-start;cursor:pointer;gap:12px;padding:5px;">
                            <input type="checkbox" id="kvkk_approval" name="kvkk_approval" required style="margin-top:3px;appearance:none;-webkit-appearance:none;width:22px;height:22px;border:2px solid #cbd5e1;border-radius:6px;position:relative;cursor:pointer;transition:all 0.2s ease;">
                            <span style="font-size:15px;color:#334155;line-height:1.5;">KVKK kapsamında verilerimin işlenmesini onaylıyorum. *</span>
                        </label>
                        <a href="#" class="kvkk-link" style="display:block;margin-top:10px;margin-left:34px;font-size:14px;color:#ffc904;text-decoration:none;font-weight:500;transition:all 0.2s ease;">KVKK metnini okumak için tıklayınız</a>
                    </div>

                    <div class="form-actions" style="margin-top:40px;text-align:center;">
                        <button type="button" id="start-test-btn" class="button button-primary" style="background:linear-gradient(45deg, #ffc904, #ffd234);color:#1a1a1a;border:none;padding:18px 48px;font-size:17px;border-radius:14px;cursor:pointer;font-weight:700;transition:all 0.3s ease;box-shadow:0 8px 16px rgba(255, 201, 4, 0.2);min-width:260px;text-transform:uppercase;letter-spacing:0.5px;">Testi Başlat</button>
                        <p style="margin-top:16px;font-size:14px;color:#64748b;">* ile işaretli alanların doldurulması zorunludur</p>
                    </div>
                </div>
            </div>

            <!-- Test Soruları -->
            <div id="lpt-test-questions" class="lpt-section" style="display: none; margin: 0 auto; padding: 20px; max-width: 900px; position: relative;">
                <div id="lpt-test-form" style="width: 100%;">
                    <input type="hidden" id="student_info" name="student_info" value="">
                    
                    <!-- İlerleme çubuğu -->
                    <div class="test-progress" style="margin-bottom: 40px; background-color: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: sticky; top: 20px; z-index: 100;">
                        <h3 style="margin: 0 0 15px 0; color: #1a1a1a; font-size: 20px; text-align: center; font-weight: 700;">Sınav İlerlemesi</h3>
                        <div class="progress-bar" style="height: 10px; background-color: #f1f5f9; border-radius: 10px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                            <div class="progress-fill" style="height: 100%; width: 0%; background: linear-gradient(45deg, #ffc904, #ffd234); transition: width 0.3s ease;"></div>
                        </div>
                        <div class="progress-text" style="text-align: center; margin-top: 12px; font-size: 15px; color: #64748b; font-weight: 500;"></div>
                    </div>
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="lpt-question" data-question-id="<?php echo $question->id; ?>" style="margin-bottom: 30px; padding: 30px; background: #ffffff; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                            <h3 style="margin: 0 0 25px 0; color: #1a1a1a; font-size: 18px; line-height: 1.5; font-weight: 600; display: flex; gap: 16px; align-items: flex-start;">
                                <span class="question-number" style="color: #ffc904; font-weight: 800; font-size: 20px; min-width: 28px; background: rgba(255, 201, 4, 0.1); height: 28px; width: 28px; display: flex; align-items: center; justify-content: center; border-radius: 8px;"><?php echo $index + 1; ?></span>
                                <span style="flex: 1;"><?php echo esc_html($question->question_text); ?></span>
                            </h3>
                            <?php
                            $options = json_decode($question->options, true);
                            if ($question->question_type === 'multiple_choice' && is_array($options)):
                                foreach ($options as $letter => $option):
                            ?>
                            <label class="option-label" style="display: flex; align-items: center; margin: 0 0 16px 0; padding: 16px 20px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s ease; position: relative; overflow: hidden;">
                                <input type="radio" name="question_<?php echo $question->id; ?>" value="<?php echo esc_attr($letter); ?>" class="option-input" style="appearance: none; -webkit-appearance: none; width: 22px; height: 22px; border: 2px solid #cbd5e1; border-radius: 50%; margin-right: 16px; position: relative; transition: all 0.2s ease; flex-shrink: 0;">
                                <span class="option-text" style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                    <span class="option-letter" style="font-weight: 700; color: #64748b; min-width: 24px; font-size: 16px;"><?php echo esc_html($letter); ?>)</span>
                                    <span class="option-content" style="color: #334155; line-height: 1.5; font-size: 16px;"><?php echo esc_html($option); ?></span>
                                </span>
                            </label>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-actions" style="margin: 40px 0; text-align: center; padding: 20px;">
                        <button type="button" id="submit-test-btn" class="button button-primary" style="background: linear-gradient(45deg, #ffc904, #ffd234); color: #1a1a1a; border: none; padding: 16px 40px; font-size: 17px; border-radius: 12px; cursor: pointer; font-weight: 700; transition: all 0.3s ease; box-shadow: 0 8px 16px rgba(255, 201, 4, 0.2); min-width: 240px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Testi Tamamla
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sonuç Ekranı -->
            <div id="lpt-result" class="lpt-section" style="margin: 2em auto; padding: 2em;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_test_submission() {
        global $wpdb;
        
        error_log('Test submission received');
        error_log('POST data: ' . print_r($_POST, true));
        
        check_ajax_referer('lpt-nonce', 'nonce');

        $answers = isset($_POST['answers']) ? json_decode(stripslashes($_POST['answers']), true) : array();
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        $student_info = isset($_POST['student_info']) ? json_decode(stripslashes($_POST['student_info']), true) : array();
        
        error_log('Processed data:');
        error_log('Answers: ' . print_r($answers, true));
        error_log('Language: ' . $language);
        error_log('Student info: ' . print_r($student_info, true));
        
        if (empty($answers) || empty($language) || empty($student_info)) {
            error_log('Invalid submission - missing data');
            wp_send_json_error(__('Invalid submission', 'language-proficiency-test'));
            return;
        }

        $score = 0;
        $total_questions = count($answers);
        
        foreach ($answers as $question_id => $answer) {
            $question = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lpt_questions WHERE id = %d",
                $question_id
            ));
            
            if ($question && $question->correct_answer === $answer) {
                $score += $question->points;
            }
        }

        $percentage = ($score / $total_questions) * 100;
        $level = $this->determine_level($percentage);

        error_log('Test results:');
        error_log(sprintf('Score: %d/%d (%.2f%%) - Level: %s', $score, $total_questions, $percentage, $level));

        // Test sonucunu kaydet
        $test_data = array(
            'answers' => $answers,
            'student_info' => array(
                'name' => sanitize_text_field($student_info['name']),
                'email' => sanitize_email($student_info['email']),
                'phone' => sanitize_text_field($student_info['phone'])
            )
        );

        $result = LPT_Database::save_result(
            0, // Anonim kullanıcı için 0 kullanıyoruz
            $language, 
            $score, 
            $level, 
            $test_data
        );

        error_log('Save result: ' . ($result ? 'success' : 'failed'));
        
        if (!$result) {
            error_log('Database Error: ' . $wpdb->last_error);
        }

        // Öğrenme amacına göre özel mesaj ekle
        $purpose = isset($student_info['purpose']) ? $student_info['purpose'] : '';
        $additional_message = '';

        switch ($purpose) {
            case 'academic':
                $additional_message = '12 yıllık yurtdışı eğitim danışmanlığı deneyimimizle, yurtdışında eğitim sürecinizde yanınızdayız. Ücretsiz danışmanlık hizmetimiz ve vize başvuru desteğimiz ile hayallerinize bir adım daha yaklaşın. <a href="http://eduyurtdisiegitim.com" target="_blank" class="result-cta">Ücretsiz danışmanlık randevusu alın</a>.';
                break;
            case 'travel':
                $additional_message = 'Seyahat ve günlük konuşma odaklı özel derslerimiz ile 3 ay gibi kısa bir sürede temel iletişim becerilerini kazanın. Native speaker eğitmenlerimiz ve pratik odaklı müfredatımız ile seyahatlerinizde kendinizi güvende hissedin. <a href="/iletisim" class="result-cta">Pratik odaklı eğitim programı için bilgi alın</a>.';
                break;
            case 'business':
                $additional_message = 'İş İngilizcesi ve profesyonel iletişim konusunda uzmanlaşmış eğitmenlerimiz, sektöre özel terminoloji ve sunum teknikleri eğitimlerimiz ile kariyerinizde fark yaratın. Kurumsal eğitim programlarımız ve bire bir koçluk seçeneklerimiz hakkında bilgi almak için <a href="/iletisim" class="result-cta">bizimle iletişime geçin</a>.';
                break;
            case 'personal':
                $additional_message = 'Kişisel gelişiminiz için özel tasarlanmış programlarımızdan yararlanın. Şu anda devam eden "2 kişi gel 1 kişi öde" kampanyamız ile hem bütçe dostu hem de motivasyon artırıcı grup derslerimize katılın. Üstelik ilk 2 ders deneme dersi fırsatı ile! <a href="/iletisim" class="result-cta">Kampanya detayları için hemen iletişime geçin</a>.';
                break;
            case 'other':
                $additional_message = 'Her seviye ve her amaç için özel tasarlanmış eğitim programlarımız ve uygun ödeme seçeneklerimiz ile hayalinizdeki dil eğitimine başlayın. "2 kişi gel 1 kişi öde" kampanyamız ve grup dersi avantajlarımız hakkında bilgi almak için <a href="/iletisim" class="result-cta">bizimle iletişime geçin</a>.';
                break;
        }

        $response = array(
            'score' => $score,
            'total' => $total_questions,
            'percentage' => $percentage,
            'level' => $level,
            'student_info' => $student_info,
            'message' => sprintf(
                '<div class="lpt-result-message" style="background: linear-gradient(to bottom right, #ffffff, #f8fafc); padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); text-align: center; max-width: 900px; margin: 0 auto; position: relative; overflow: hidden;">' .
                '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, #ffc904, #ffd234);"></div>' .
                '<h3 style="font-size: 32px; color: #1a1a1a; margin: 30px 0; font-weight: 800; line-height: 1.3; background: linear-gradient(45deg, #1a1a1a, #2c3e50); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Seviye Tespit Sınavınız Tamamlandı</h3>' .
                '<div class="result-level" style="display: inline-block; font-size: 72px; font-weight: 800; color: #ffc904; margin: 30px 0; padding: 20px 40px; border-radius: 20px; background: rgba(255, 201, 4, 0.1); box-shadow: 0 8px 24px rgba(255, 201, 4, 0.15);">%s</div>' .
                '<div class="result-score" style="font-size: 20px; color: #1a1a1a; margin: 25px 0; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">' .
                '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 201, 4, 0.1); border-radius: 12px; color: #ffc904; font-weight: 800;">%d</span>' .
                '<span style="color: #64748b;">/</span>' .
                '<span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: #f1f5f9; border-radius: 12px; color: #64748b; font-weight: 600;">%d</span>' .
                '<span style="margin-left: 16px; padding: 8px 16px; background: #f1f5f9; border-radius: 12px; color: #64748b; font-weight: 600;">%%%d başarı</span>' .
                '</div>' .
                '<div class="result-message" style="font-size: 18px; color: #1a1a1a; line-height: 1.6; margin: 30px 0; padding: 0 20px;">Sayın <strong style="color: #1a1a1a; font-weight: 700;">%s</strong>, seviye tespit sınavınız başarıyla tamamlanmıştır.</div>' .
                '<div class="result-additional" style="background: #ffffff; padding: 30px; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); text-align: left; margin: 40px 0; font-size: 16px; line-height: 1.6; color: #334155; box-shadow: 0 8px 24px rgba(0,0,0,0.04);">' .
                '<div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 20px;">' .
                '<div style="width: 32px; height: 32px; flex-shrink: 0; background: rgba(255, 201, 4, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">' .
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#ffc904" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.09 9C9.3251 8.33167 9.78915 7.76811 10.4 7.40913C11.0108 7.05016 11.7289 6.91894 12.4272 7.03871C13.1255 7.15849 13.7588 7.52152 14.2151 8.06353C14.6713 8.60553 14.9211 9.29152 14.92 10C14.92 12 11.92 13 11.92 13" stroke="#ffc904" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17H12.01" stroke="#ffc904" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' .
                '</div>' .
                '<div style="flex: 1;">%s</div>' .
                '</div>' .
                '</div>' .
                '</div>',
                $level,
                $score,
                $total_questions,
                $percentage,
                esc_html($student_info['name']),
                $additional_message
            )
        );

        if ($additional_message) {
            // $response['message'] .= '<br><br>' . $additional_message; 
            // Bu satırı kaldırdık çünkü zaten yukarıda mesaja ekledik
        }

        // Response içeriğini detaylı olarak loglayalım
        error_log('Response message content being sent to client: ' . $response['message']);

        // E-posta gönderimi
        $to = 'sdm@sdm.com.tr';
        $subject = 'Yeni Seviye Tespit Sınavı Sonucu';
        
        // Dil adını al
        $language_names = array(
            'english' => 'İngilizce',
            'spanish' => 'İspanyolca',
            'french' => 'Fransızca',
            'german' => 'Almanca',
            'italian' => 'İtalyanca'
        );
        $language_name = isset($language_names[$language]) ? $language_names[$language] : ucfirst($language);

        // Öğrenme amacını al
        $purpose_names = array(
            'academic' => 'Akademik amaçla',
            'travel' => 'Seyahat amacıyla',
            'business' => 'İş amacıyla',
            'personal' => 'Kişisel gelişim amacıyla',
            'other' => 'Diğer amaçlarla'
        );
        $purpose_name = isset($purpose_names[$purpose]) ? $purpose_names[$purpose] : $purpose;

        $message = sprintf(
            "Yeni bir seviye tespit sınavı tamamlandı.\n\n" .
            "Öğrenci Bilgileri:\n" .
            "Ad Soyad: %s\n" .
            "E-posta: %s\n" .
            "Telefon: %s\n" .
            "Dil: %s\n" .
            "Öğrenme Amacı: %s\n\n" .
            "Sınav Sonuçları:\n" .
            "Seviye: %s\n" .
            "Doğru Sayısı: %d/%d\n" .
            "Başarı Yüzdesi: %%%d",
            $student_info['name'],
            $student_info['email'],
            $student_info['phone'],
            $language_name,
            $purpose_name,
            $level,
            $score,
            $total_questions,
            $percentage
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // E-posta gönder
        $mail_sent = wp_mail($to, $subject, $message, $headers);
        
        if (!$mail_sent) {
            error_log('E-posta gönderilemedi');
        }
        
        error_log('Sending response: ' . print_r($response, true));
        wp_send_json_success($response);
    }

    private function determine_level($percentage) {
        if ($percentage >= 90) return 'C2';
        if ($percentage >= 80) return 'C1';
        if ($percentage >= 70) return 'B2';
        if ($percentage >= 60) return 'B1';
        if ($percentage >= 40) return 'A2';
        return 'A1';
    }
} 