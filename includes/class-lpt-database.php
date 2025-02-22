<?php
class LPT_Database {
    public static function create_tables() {
        global $wpdb;
        $wpdb->show_errors(true);
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Diller tablosu
        $languages_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lpt_languages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";

        // Varsayılan dilleri ekle
        $default_languages = array(
            array('code' => 'english', 'name' => 'İngilizce'),
            array('code' => 'spanish', 'name' => 'İspanyolca'),
            array('code' => 'french', 'name' => 'Fransızca'),
            array('code' => 'german', 'name' => 'Almanca'),
            array('code' => 'italian', 'name' => 'İtalyanca')
        );

        // Questions table
        $questions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lpt_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            language varchar(50) NOT NULL,
            question_text text NOT NULL,
            question_type varchar(20) NOT NULL,
            level varchar(10) NOT NULL,
            options longtext NOT NULL,
            correct_answer varchar(1) NOT NULL,
            points int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Test Results table
        $results_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lpt_test_results (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            language varchar(50) NOT NULL,
            score int(11) NOT NULL,
            level varchar(10) NOT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            answers longtext,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY language (language),
            KEY level (level)
        ) $charset_collate;";

        // Tabloları oluştur
        dbDelta($languages_table);
        dbDelta($questions_table);
        dbDelta($results_table);

        // Varsayılan dilleri ekle
        foreach ($default_languages as $lang) {
            $wpdb->replace(
                $wpdb->prefix . 'lpt_languages',
                array(
                    'code' => $lang['code'],
                    'name' => $lang['name']
                ),
                array('%s', '%s')
            );
        }
    }

    public static function get_languages() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lpt_languages ORDER BY name ASC");
    }

    public static function add_language($code, $name) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . 'lpt_languages',
            array(
                'code' => sanitize_text_field($code),
                'name' => sanitize_text_field($name)
            ),
            array('%s', '%s')
        );
    }

    public static function delete_language($id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . 'lpt_languages',
            array('id' => $id),
            array('%d')
        );
    }

    public static function get_questions($language, $limit = 20) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lpt_questions 
                WHERE language = %s 
                ORDER BY RAND() 
                LIMIT %d",
                $language,
                $limit
            )
        );
    }

    public static function save_result($user_id, $language, $score, $level, $answers) {
        global $wpdb;
        $wpdb->show_errors(true);

        error_log('Saving test result:');
        error_log('User ID: ' . $user_id);
        error_log('Language: ' . $language);
        error_log('Score: ' . $score);
        error_log('Level: ' . $level);
        error_log('Answers: ' . print_r($answers, true));

        try {
            $data = array(
                'user_id' => $user_id,
                'language' => $language,
                'score' => $score,
                'level' => $level,
                'answers' => json_encode($answers),
                'completed_at' => current_time('mysql')
            );

            $format = array(
                '%d',  // user_id
                '%s',  // language
                '%d',  // score
                '%s',  // level
                '%s',  // answers
                '%s'   // completed_at
            );

            error_log('Inserting data: ' . print_r($data, true));

            $result = $wpdb->insert(
                $wpdb->prefix . 'lpt_test_results',
                $data,
                $format
            );

            if ($result === false) {
                error_log('Database insert error: ' . $wpdb->last_error);
                return false;
            }

            $insert_id = $wpdb->insert_id;
            error_log('Test result saved successfully. ID: ' . $insert_id);
            return $insert_id;

        } catch (Exception $e) {
            error_log('Exception saving test result: ' . $e->getMessage());
            return false;
        }
    }

    public static function get_user_results($user_id) {
        global $wpdb;
        $wpdb->show_errors(true);

        try {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}lpt_test_results 
                    WHERE user_id = %d 
                    ORDER BY completed_at DESC",
                    $user_id
                )
            );
        } catch (Exception $e) {
            error_log('Error getting user results: ' . $e->getMessage());
            return array();
        }
    }

    public static function delete_question($id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . 'lpt_questions',
            array('id' => $id),
            array('%d')
        );
    }
} 