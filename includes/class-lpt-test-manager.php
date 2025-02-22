<?php
class LPT_Test_Manager {
    public function init() {
        add_shortcode('language_test', array($this, 'render_test'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
        add_action('wp_ajax_submit_language_test', array($this, 'handle_test_submission'));
        add_action('wp_ajax_nopriv_submit_language_test', array($this, 'handle_test_submission'));

        // Debug için
        add_action('wp_footer', function() {
            ?>
            <script>
            console.log('PHP debug: Script yükleniyor...');
            document.addEventListener('DOMContentLoaded', function() {
                console.log('PHP debug: DOM yüklendi');
                var startButton = document.getElementById('start-test-btn');
                console.log('PHP debug: Start button:', startButton);
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
            
            // Ana script'i yükle
            wp_enqueue_script('lpt-script', LPT_PLUGIN_URL . 'assets/js/script.js', array('jquery'), time(), true);
            
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
        <div class="lpt-test-container" data-language="<?php echo esc_attr($atts['language']); ?>">
            <div id="lpt-student-info" class="lpt-section active">
                <h2>Seviye Tespit Sınavınızı Başlatmanız İçin Bilgilerinizi Girin</h2>
                <div id="lpt-info-form" class="lpt-form">
                    <div class="form-group">
                        <label for="student_name" class="form-label">İsim Soyisim *</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="student_email" class="form-label">E-posta *</label>
                        <input type="email" id="student_email" name="student_email" required>
                    </div>
                    <div class="form-group">
                        <label for="student_phone" class="form-label">Telefon Numarası *</label>
                        <input type="tel" id="student_phone" name="student_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="learning_purpose" class="form-label purpose-label">
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
                        <select name="learning_purpose" id="learning_purpose" required class="purpose-select">
                            <option value="">Lütfen seçiniz</option>
                            <option value="academic">Akademik amaçla öğrenmek istiyorum</option>
                            <option value="travel">Seyahat amacıyla öğrenmek istiyorum</option>
                            <option value="business">İş amacıyla öğrenmek istiyorum</option>
                            <option value="personal">Kişisel gelişim amacıyla öğrenmek istiyorum</option>
                            <option value="other">Diğer amaçlarla öğrenmek istiyorum</option>
                        </select>
                    </div>

                    <!-- KVKK Onayı -->
                    <div class="form-group kvkk-row">
                        <label class="kvkk-label" id="kvkk-label-important">
                            <input type="checkbox" id="kvkk_approval" name="kvkk_approval" required>
                            <span>KVKK kapsamında verilerimin işlenmesini onaylıyorum. *</span>
                        </label>
                        <a href="#" class="kvkk-link">KVKK metnini okumak için tıklayınız</a>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="start-test-btn" class="button button-primary">
                            Testi Başlat
                        </button>
                    </div>
                </div>
            </div>

            <!-- Test Soruları -->
            <div id="lpt-test-questions" class="lpt-section" style="display: none;">
                <div id="lpt-test-form">
                    <input type="hidden" id="student_info" name="student_info" value="">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="lpt-question" data-question-id="<?php echo $question->id; ?>">
                            <h3>
                                <span class="question-number"><?php echo $index + 1; ?>.</span>
                                <?php echo esc_html($question->question_text); ?>
                            </h3>
                            <?php
                            $options = json_decode($question->options, true);
                            if ($question->question_type === 'multiple_choice' && is_array($options)):
                                foreach ($options as $letter => $option):
                            ?>
                                <label class="option-label">
                                    <input type="radio" name="question_<?php echo $question->id; ?>" value="<?php echo esc_attr($letter); ?>" class="option-input">
                                    <span class="option-text">
                                        <span class="option-letter"><?php echo esc_html($letter); ?>)</span>
                                        <span class="option-content"><?php echo esc_html($option); ?></span>
                                    </span>
                                </label>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-actions">
                        <button type="button" id="submit-test-btn" class="button button-primary" onclick="LPTTest.submitTest()">
                            Testi Tamamla
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sonuç Ekranı -->
            <div id="lpt-result" class="lpt-section" style="display: none;"></div>
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

        $response = array(
            'score' => $score,
            'total' => $total_questions,
            'percentage' => $percentage,
            'level' => $level,
            'student_info' => $student_info,
            'message' => sprintf(
                '<div class="lpt-result-message">' .
                '<h3>Seviye Tespit Sınavınız Tamamlandı</h3>' .
                '<div class="result-level">%s</div>' .
                '<div class="result-score">%d/%d doğru (%%%d başarı)</div>' .
                '<div class="result-message">Sayın <strong>%s</strong>, seviye tespit sınavınız başarıyla tamamlanmıştır.</div>' .
                '<div class="result-additional">%s</div>' .
                '</div>',
                $level,
                $score,
                $total_questions,
                $percentage,
                esc_html($student_info['name']),
                $additional_message
            )
        );

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

        if ($additional_message) {
            $response['message'] .= '<br><br>' . $additional_message;
        }

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