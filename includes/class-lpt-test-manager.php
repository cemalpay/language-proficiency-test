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
        <div class="lpt-test-container" data-language="<?php echo esc_attr($atts['language']); ?>" style="max-width: 800px; margin: 2em auto; padding: 2em; background: #fff; box-shadow: 0 0 20px rgba(0, 0, 0, 0.08); border-radius: 10px; position: relative; z-index: 1;">
            <!-- Öğrenci Bilgi Formu -->
            <div id="lpt-student-info" class="lpt-section active" style="display: block; position: relative;">
                <h2 style="font-size: 26px; color: #2c3e50; margin-bottom: 25px; text-align: center; font-weight: 700;">Seviye Tespit Sınavınızı Başlatmanız İçin Bilgilerinizi Girin</h2>
                <div id="lpt-info-form" class="lpt-form" style="background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid #e5e5e5; width: 100%; display: block; box-shadow: 0 5px 15px rgba(0,0,0,0.03);">
                    <div class="form-group" style="margin-bottom: 20px; position: relative; z-index: 2; width: 100%; display: block;">
                        <label for="student_name" class="form-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; line-height: 1.4; width: 100%; position: relative; text-align: left;">İsim Soyisim *</label>
                        <input type="text" id="student_name" name="student_name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; transition: all 0.2s ease; background-color: white;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px; position: relative; z-index: 2; width: 100%; display: block;">
                        <label for="student_email" class="form-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; line-height: 1.4; width: 100%; position: relative; text-align: left;">E-posta *</label>
                        <input type="email" id="student_email" name="student_email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; transition: all 0.2s ease; background-color: white;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px; position: relative; z-index: 2; width: 100%; display: block;">
                        <label for="student_phone" class="form-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; line-height: 1.4; width: 100%; position: relative; text-align: left;">Telefon Numarası *</label>
                        <input type="tel" id="student_phone" name="student_phone" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; transition: all 0.2s ease; background-color: white;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px; position: relative; z-index: 10; width: 100%; display: block;">
                        <label for="learning_purpose" class="form-label purpose-label" style="display: block; margin-bottom: 12px; font-weight: 600; color: #1a1a1a; font-size: 15px; line-height: 1.4; width: 100%; position: relative; text-align: left;">
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
                        </label>
                        <select name="learning_purpose" id="learning_purpose" required class="purpose-select" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; background-color: #fff; height: auto; appearance: auto; -webkit-appearance: auto; -moz-appearance: auto; cursor: pointer; transition: all 0.2s ease; color: #475569; background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23475569%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpolyline points=%226 9 12 15 18 9%22%3E%3C/polyline%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 12px center; padding-right: 40px;">
                            <option value="">Lütfen seçiniz</option>
                            <option value="academic">Akademik amaçla öğrenmek istiyorum</option>
                            <option value="travel">Seyahat amacıyla öğrenmek istiyorum</option>
                            <option value="business">İş amacıyla öğrenmek istiyorum</option>
                            <option value="personal">Kişisel gelişim amacıyla öğrenmek istiyorum</option>
                            <option value="other">Diğer amaçlarla öğrenmek istiyorum</option>
                        </select>
                    </div>

                    <!-- KVKK Onayı -->
                    <div class="form-group kvkk-row" style="margin-bottom: 25px; padding: 15px; background-color: #f1f5f9; border-radius: 8px; border-left: 3px solid #3498db;">
                        <label class="kvkk-label" id="kvkk-label-important" style="display: flex; align-items: flex-start; cursor: pointer; gap: 10px; padding: 5px;">
                            <input type="checkbox" id="kvkk_approval" name="kvkk_approval" required style="margin-top: 3px; appearance: none; -webkit-appearance: none; width: 18px; height: 18px; border: 2px solid #cbd5e1; border-radius: 4px; position: relative; cursor: pointer; transition: all 0.2s ease;">
                            <span style="font-size: 14px; color: #334155; line-height: 1.5;">KVKK kapsamında verilerimin işlenmesini onaylıyorum. *</span>
                        </label>
                        <a href="#" class="kvkk-link" style="display: block; margin-top: 8px; margin-left: 28px; font-size: 13px; color: #3498db; text-decoration: underline;">KVKK metnini okumak için tıklayınız</a>
                    </div>

                    <div class="form-actions" style="margin-top: 30px; text-align: center;">
                        <button type="button" id="start-test-btn" class="button button-primary" style="background-color: #3498db; color: white; border: none; padding: 14px 28px; font-size: 16px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2); min-width: 200px;">
                            Testi Başlat
                        </button>
                    </div>
                </div>
            </div>

            <!-- Test Soruları -->
            <div id="lpt-test-questions" class="lpt-section" style="display: none; margin: 0 auto; padding: 20px; max-width: 800px; position: relative;">
                <div id="lpt-test-form" style="width: 100%;">
                    <input type="hidden" id="student_info" name="student_info" value="">
                    
                    <!-- İlerleme çubuğu - JS tarafından eklenmektedir, burada stil ekleyeceğiz -->
                    <div class="test-progress" style="margin-bottom: 30px; background-color: #f8fafc; padding: 15px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <h3 style="margin-top: 0; margin-bottom: 10px; color: #2c3e50; font-size: 18px; text-align: center; font-weight: 600;">Sınav İlerlemesi: 1. Soru</h3>
                        <div class="progress-bar" style="height: 12px; background-color: #e2e8f0; border-radius: 6px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                            <div class="progress-fill" style="height: 100%; width: 0%; background: linear-gradient(to right, #3498db, #2980b9); transition: width 0.3s ease;"></div>
                        </div>
                        <div class="progress-text" style="text-align: center; margin-top: 8px; font-size: 14px; color: #64748b;"></div>
                    </div>
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="lpt-question" data-question-id="<?php echo $question->id; ?>" style="margin-bottom: 2em; padding: 2em; background: #ffffff; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                            <h3 style="margin-top: 0; margin-bottom: 1.5em; color: #2c3e50; font-size: 1.3em; line-height: 1.4; font-weight: 600; display: flex; gap: 15px;">
                                <span class="question-number" style="color: #3498db; font-weight: 700; min-width: 28px;"><?php echo $index + 1; ?>.</span>
                                <?php echo esc_html($question->question_text); ?>
                            </h3>
                            <?php
                            $options = json_decode($question->options, true);
                            if ($question->question_type === 'multiple_choice' && is_array($options)):
                                foreach ($options as $letter => $option):
                            ?>
                                <label class="option-label" style="display: flex; align-items: center; margin: 0.8em 0; padding: 0.8em 1em; background: #f8fafc; border: 1px solid #edf2f7; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; position: relative;">
                                    <input type="radio" name="question_<?php echo $question->id; ?>" value="<?php echo esc_attr($letter); ?>" class="option-input" style="appearance: none; -webkit-appearance: none; width: 16px; height: 16px; border: 2px solid #cbd5e1; border-radius: 50%; margin-right: 12px; position: relative; transition: all 0.2s ease; flex-shrink: 0;">
                                    <span class="option-text" style="display: flex; align-items: center; gap: 8px;">
                                        <span class="option-letter" style="font-weight: 600; color: #475569; min-width: 24px;"><?php echo esc_html($letter); ?>)</span>
                                        <span class="option-content" style="color: #334155; line-height: 1.5;"><?php echo esc_html($option); ?></span>
                                    </span>
                                </label>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-actions" style="margin-top: 30px; text-align: center; padding: 20px;">
                        <button type="button" id="submit-test-btn" class="button button-primary" style="background-color: #3498db; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2); min-width: 200px;">
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
                '<div class="lpt-result-message" style="background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 800px; margin: 0 auto;">' .
                '<h3 style="color: #2c3e50; font-size: 24px; margin-bottom: 25px; font-weight: 700;">Seviye Tespit Sınavınız Tamamlandı</h3>' .
                '<div class="result-level" style="font-size: 72px; font-weight: 800; color: #3498db; margin: 30px 0;">%s</div>' .
                '<div class="result-score" style="font-size: 18px; color: #34495e; margin-bottom: 20px; font-weight: 600;">%d/%d doğru (%%%d başarı)</div>' .
                '<div class="result-message" style="font-size: 16px; color: #5c6c7c; line-height: 1.6; margin-bottom: 30px;">Sayın <strong>%s</strong>, seviye tespit sınavınız başarıyla tamamlanmıştır.</div>' .
                '<div class="result-additional" style="background-color: #f8fafc; padding: 20px; border-radius: 10px; border-left: 5px solid #3498db; text-align: left; margin-top: 30px; font-size: 15px; line-height: 1.5; color: #414c59;">%s</div>' .
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