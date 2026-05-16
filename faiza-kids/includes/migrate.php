<?php
/**
 * Faiza Kids Concierge - Database Migration System
 * Safe migrations: create tables if missing, add columns if missing, seed default data.
 */

function run_migrations(PDO $pdo): array {
    $messages = [];

    $schemas = get_table_schemas();
    foreach ($schemas as $table => $sql) {
        try {
            $pdo->exec($sql);
            $messages[] = "✓ Table `$table` vérifiée/créée.";
        } catch (PDOException $e) {
            $messages[] = "✗ Erreur table `$table` : " . $e->getMessage();
        }
    }

    // Add missing columns to existing tables
    $column_migrations = get_column_migrations();
    foreach ($column_migrations as $table => $columns) {
        foreach ($columns as $col => $def) {
            if (!column_exists($pdo, $table, $col)) {
                try {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $col $def");
                    $messages[] = "✓ Colonne `$col` ajoutée à `$table`.";
                } catch (PDOException $e) {
                    $messages[] = "✗ Erreur ajout colonne `$col` dans `$table` : " . $e->getMessage();
                }
            }
        }
    }

    // Seed default data
    seed_admin($pdo, $messages);
    seed_hotels($pdo, $messages);
    seed_settings($pdo, $messages);
    seed_whatsapp_templates($pdo, $messages);
    seed_qr_links($pdo, $messages);

    return $messages;
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function get_table_schemas(): array {
    return [
        'admins' => "CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `full_name` VARCHAR(255) DEFAULT NULL,
            `avatar` VARCHAR(255) DEFAULT NULL,
            `last_login` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'settings' => "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'hotels' => "CREATE TABLE IF NOT EXISTS `hotels` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `code` VARCHAR(20) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `notification_email` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `logo_path` VARCHAR(255) DEFAULT NULL,
            `primary_color` VARCHAR(20) DEFAULT '#2D6A4F',
            `secondary_color` VARCHAR(20) DEFAULT '#52B788',
            `accent_color` VARCHAR(20) DEFAULT '#E8C342',
            `public_title` VARCHAR(255) DEFAULT NULL,
            `public_text` TEXT DEFAULT NULL,
            `day_rate` DECIMAL(10,2) DEFAULT 150.00,
            `night_rate` DECIMAL(10,2) DEFAULT 200.00,
            `night_start_hour` TIME DEFAULT '20:00:00',
            `night_end_hour` TIME DEFAULT '08:00:00',
            `min_booking_hours` INT DEFAULT 24,
            `cancellation_hours` INT DEFAULT 3,
            `whatsapp_intro` TEXT DEFAULT NULL,
            `whatsapp_confirmation` TEXT DEFAULT NULL,
            `whatsapp_reminder` TEXT DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'babysitters' => "CREATE TABLE IF NOT EXISTS `babysitters` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `full_name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `languages` VARCHAR(255) DEFAULT NULL,
            `zones` VARCHAR(255) DEFAULT NULL,
            `certifications` VARCHAR(255) DEFAULT NULL,
            `experience_years` INT DEFAULT 0,
            `rating` DECIMAL(3,1) DEFAULT 5.0,
            `notes` TEXT DEFAULT NULL,
            `internal_notes` TEXT DEFAULT NULL,
            `status` ENUM('available','busy','vacation','inactive') DEFAULT 'available',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'babysitter_hotels' => "CREATE TABLE IF NOT EXISTS `babysitter_hotels` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `babysitter_id` INT NOT NULL,
            `hotel_id` INT NOT NULL,
            UNIQUE KEY `unique_assignment` (`babysitter_id`,`hotel_id`),
            KEY `fk_bh_baby` (`babysitter_id`),
            KEY `fk_bh_hotel` (`hotel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'bookings' => "CREATE TABLE IF NOT EXISTS `bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reference` VARCHAR(50) NOT NULL UNIQUE,
            `type` ENUM('hotel','city') NOT NULL,
            `source` VARCHAR(100) DEFAULT 'direct',
            `hotel_id` INT DEFAULT NULL,
            `client_name` VARCHAR(255) NOT NULL,
            `client_whatsapp` VARCHAR(50) NOT NULL,
            `client_email` VARCHAR(255) DEFAULT NULL,
            `client_language` ENUM('fr','en','ar') DEFAULT 'fr',
            `room_number` VARCHAR(50) DEFAULT NULL,
            `city` VARCHAR(100) DEFAULT NULL,
            `area` VARCHAR(100) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `location_notes` TEXT DEFAULT NULL,
            `service_date` DATE NOT NULL,
            `start_time` TIME NOT NULL,
            `duration_minutes` INT NOT NULL DEFAULT 120,
            `end_time` TIME DEFAULT NULL,
            `children_count` INT NOT NULL DEFAULT 1,
            `special_needs` TEXT DEFAULT NULL,
            `internal_notes` TEXT DEFAULT NULL,
            `babysitter_id` INT DEFAULT NULL,
            `babysitters_count` INT DEFAULT 1,
            `suggested_price` DECIMAL(10,2) DEFAULT NULL,
            `final_price` DECIMAL(10,2) DEFAULT NULL,
            `price_status` ENUM('pending','proposed','confirmed','free') DEFAULT 'pending',
            `price_notes` TEXT DEFAULT NULL,
            `status` ENUM('new','pending','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'new',
            `payment_status` ENUM('not_required','pending','requested','proof_sent','validated','refused','refunded') DEFAULT 'pending',
            `payment_method` VARCHAR(50) DEFAULT 'bank_transfer',
            `payment_notes` TEXT DEFAULT NULL,
            `secure_token` VARCHAR(100) DEFAULT NULL,
            `cancelled_at` DATETIME DEFAULT NULL,
            `cancellation_reason` TEXT DEFAULT NULL,
            `confirmed_at` DATETIME DEFAULT NULL,
            `completed_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_service_date` (`service_date`),
            KEY `idx_status` (`status`),
            KEY `idx_type` (`type`),
            KEY `idx_hotel` (`hotel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'booking_children' => "CREATE TABLE IF NOT EXISTS `booking_children` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT NOT NULL,
            `child_name` VARCHAR(255) DEFAULT NULL,
            `age` INT DEFAULT NULL,
            `allergies` TEXT DEFAULT NULL,
            `special_needs` TEXT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            KEY `fk_bc_booking` (`booking_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'payment_proofs' => "CREATE TABLE IF NOT EXISTS `payment_proofs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_name` VARCHAR(255) DEFAULT NULL,
            `file_size` INT DEFAULT NULL,
            `file_type` VARCHAR(50) DEFAULT NULL,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `verified_at` DATETIME DEFAULT NULL,
            `verified_by` INT DEFAULT NULL,
            `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
            `notes` TEXT DEFAULT NULL,
            KEY `fk_pp_booking` (`booking_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'whatsapp_templates' => "CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `template_key` VARCHAR(100) NOT NULL,
            `language` ENUM('fr','en','ar') NOT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `message` TEXT NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_template` (`template_key`,`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'whatsapp_history' => "CREATE TABLE IF NOT EXISTS `whatsapp_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT DEFAULT NULL,
            `admin_id` INT DEFAULT NULL,
            `client_name` VARCHAR(255) DEFAULT NULL,
            `client_whatsapp` VARCHAR(50) DEFAULT NULL,
            `template_key` VARCHAR(100) DEFAULT NULL,
            `language` ENUM('fr','en','ar') DEFAULT 'fr',
            `message` TEXT DEFAULT NULL,
            `action` ENUM('copied','opened','sent') DEFAULT 'copied',
            `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_wh_booking` (`booking_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'availability_blocks' => "CREATE TABLE IF NOT EXISTS `availability_blocks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` ENUM('full_day','time_slot','busy_period') NOT NULL,
            `date_start` DATE NOT NULL,
            `date_end` DATE DEFAULT NULL,
            `time_start` TIME DEFAULT NULL,
            `time_end` TIME DEFAULT NULL,
            `status` ENUM('available','limited','blocked','unavailable') DEFAULT 'blocked',
            `scope` ENUM('global','hotel','city') DEFAULT 'global',
            `hotel_id` INT DEFAULT NULL,
            `note` TEXT DEFAULT NULL,
            `created_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_ab_date` (`date_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'activity_logs' => "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id` INT DEFAULT NULL,
            `action` VARCHAR(255) NOT NULL,
            `details` TEXT DEFAULT NULL,
            `booking_id` INT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_al_booking` (`booking_id`),
            KEY `idx_al_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'qr_links' => "CREATE TABLE IF NOT EXISTS `qr_links` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `type` ENUM('hotel','city') NOT NULL,
            `hotel_id` INT DEFAULT NULL,
            `url` VARCHAR(500) NOT NULL,
            `source_param` VARCHAR(100) DEFAULT NULL,
            `scan_count` INT DEFAULT 0,
            `conversion_count` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_ql_hotel` (`hotel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'notifications' => "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(100) NOT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `booking_id` INT DEFAULT NULL,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_notif_read` (`is_read`),
            KEY `idx_notif_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

function get_column_migrations(): array {
    // Extra columns to add to existing tables if they were created without them
    return [
        'bookings' => [
            'suggested_price' => "DECIMAL(10,2) DEFAULT NULL AFTER `babysitters_count`",
            'price_notes'     => "TEXT DEFAULT NULL AFTER `price_status`",
            'secure_token'    => "VARCHAR(100) DEFAULT NULL AFTER `payment_notes`",
        ],
        'hotels' => [
            'logo_path'    => "VARCHAR(255) DEFAULT NULL AFTER `address`",
            'public_title' => "VARCHAR(255) DEFAULT NULL AFTER `accent_color`",
            'public_text'  => "TEXT DEFAULT NULL AFTER `public_title`",
        ],
    ];
}

// ── Seeders ───────────────────────────────────────────────────────────────────

function seed_admin(PDO $pdo, array &$messages): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
    if ((int)$stmt->fetchColumn() > 0) return;

    $hash = password_hash('Agadir2026@33', PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("INSERT INTO `admins` (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)")
        ->execute(['faizamultiservice', $hash, 'admin@faizamultiservice.com', 'Faiza Multiservice']);

    $messages[] = "✓ Compte admin créé (faizamultiservice).";
}

function seed_hotels(PDO $pdo, array &$messages): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `hotels`");
    if ((int)$stmt->fetchColumn() > 0) return;

    $hotels = [
        [
            'name'            => 'Fairmont Taghazout Bay',
            'slug'            => 'fairmont-taghazout-bay',
            'code'            => 'FMT',
            'primary_color'   => '#1A3A5C',
            'secondary_color' => '#2A6B8B',
            'accent_color'    => '#E8C342',
            'day_rate'        => 150.00,
            'night_rate'      => 200.00,
            'public_title'    => 'Réservez une babysitter de confiance',
            'public_text'     => 'Profitez de votre séjour pendant que Faiza Multiservice prend soin de vos enfants avec un service professionnel et certifié.',
            'sort_order'      => 1,
        ],
        [
            'name'            => 'Hilton Taghazout Bay',
            'slug'            => 'hilton-taghazout-bay',
            'code'            => 'HTB',
            'primary_color'   => '#004B87',
            'secondary_color' => '#0077C0',
            'accent_color'    => '#E8C342',
            'day_rate'        => 100.00,
            'night_rate'      => 100.00,
            'public_title'    => 'Service de babysitting premium',
            'public_text'     => 'Un service de garde d\'enfants professionnel et fiable, coordonné avec l\'hôtel Hilton Taghazout Bay.',
            'sort_order'      => 2,
        ],
        [
            'name'            => 'Sofitel Royal Bay',
            'slug'            => 'sofitel-royal-bay',
            'code'            => 'SRB',
            'primary_color'   => '#1C2B4A',
            'secondary_color' => '#C9A96E',
            'accent_color'    => '#E8C342',
            'day_rate'        => 100.00,
            'night_rate'      => 100.00,
            'public_title'    => 'Babysitting de luxe à votre service',
            'public_text'     => 'Confiez vos enfants à nos intervenantes certifiées et profitez pleinement de votre séjour au Sofitel Royal Bay.',
            'sort_order'      => 3,
        ],
        [
            'name'            => 'City / Clients externes',
            'slug'            => 'city',
            'code'            => 'CTY',
            'primary_color'   => '#2D6A4F',
            'secondary_color' => '#52B788',
            'accent_color'    => '#E8C342',
            'day_rate'        => 0.00,
            'night_rate'      => 0.00,
            'public_title'    => 'Service babysitting Agadir & région',
            'public_text'     => 'Que vous soyez en séjour à Agadir ou résident dans la région, notre service est conçu pour répondre à vos besoins.',
            'sort_order'      => 4,
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO `hotels` (name,slug,code,primary_color,secondary_color,accent_color,day_rate,night_rate,public_title,public_text,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($hotels as $h) {
        $stmt->execute([$h['name'],$h['slug'],$h['code'],$h['primary_color'],$h['secondary_color'],$h['accent_color'],$h['day_rate'],$h['night_rate'],$h['public_title'],$h['public_text'],$h['sort_order']]);
    }
    $messages[] = "✓ Hôtels par défaut insérés (4 hôtels).";
}

function seed_settings(PDO $pdo, array &$messages): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `settings`");
    if ((int)$stmt->fetchColumn() > 0) return;

    $defaults = [
        'company_name'          => 'Faiza Multiservice',
        'app_name'              => 'Faiza Kids Concierge',
        'company_email'         => 'contact@faizamultiservice.com',
        'company_phone'         => '+212 600 000 000',
        'company_whatsapp'      => '+212600000000',
        'company_city'          => 'Agadir',
        'company_address'       => 'Agadir, Maroc',
        'currency'              => 'DH',
        'default_language'      => 'fr',
        'min_booking_hours'     => '24',
        'cancellation_hours'    => '3',
        'late_cancel_refund'    => '50',
        'city_enabled'          => '1',
        'bank_name'             => 'CIH Bank',
        'bank_account_name'     => 'Faiza Multiservice',
        'bank_rib'              => '',
        'payment_instructions'  => 'Veuillez effectuer un virement bancaire au montant indiqué et envoyer la preuve de paiement via WhatsApp.',
        'smtp_host'             => '',
        'smtp_port'             => '587',
        'smtp_user'             => '',
        'smtp_pass'             => '',
        'smtp_encryption'       => 'tls',
        'smtp_from_name'        => 'Faiza Kids Concierge',
        'smtp_from_email'       => '',
        'admin_whatsapp'        => '+212600000000',
        'whatsapp_country_code' => '212',
        'pdf_footer'            => 'Faiza Multiservice – Service de babysitting premium – Agadir, Maroc',
        'logo_path'             => '',
        'login_image'           => '',
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    $messages[] = "✓ Paramètres par défaut insérés.";
}

function seed_whatsapp_templates(PDO $pdo, array &$messages): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `whatsapp_templates`");
    if ((int)$stmt->fetchColumn() > 0) return;

    $templates = [
        // ── Demande reçue ──────────────────────────────────────────────────────
        ['key'=>'demande_recue','lang'=>'fr','title'=>'Demande reçue',
         'msg'=>"Bonjour {name} 👋\n\nMerci pour votre demande de babysitting (Réf. {booking_reference}).\n\nNous avons bien reçu votre demande pour le *{date}* à *{start_time}* ({duration}) pour *{children_count} enfant(s)*.\n\nNotre équipe vérifie la disponibilité et vous contactera sous peu pour confirmer le tarif et les détails du service.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'demande_recue','lang'=>'en','title'=>'Request received',
         'msg'=>"Hello {name} 👋\n\nThank you for your babysitting request (Ref. {booking_reference}).\n\nWe received your request for *{date}* at *{start_time}* ({duration}) for *{children_count} child(ren)*.\n\nOur team is checking availability and will contact you shortly to confirm the price and service details.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'demande_recue','lang'=>'ar','title'=>'تم استلام الطلب',
         'msg'=>"مرحباً {name} 👋\n\nشكراً لطلب خدمة الجليسة (المرجع: {booking_reference}).\n\nلقد استلمنا طلبكم ليوم *{date}* الساعة *{start_time}* ({duration}) لـ *{children_count} طفل (أطفال)*.\n\nسيتواصل معكم فريقنا قريباً لتأكيد السعر وتفاصيل الخدمة.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Informations manquantes ────────────────────────────────────────────
        ['key'=>'infos_manquantes','lang'=>'fr','title'=>'Informations manquantes',
         'msg'=>"Bonjour {name},\n\nNous avons bien reçu votre demande (Réf. {booking_reference}), mais il nous manque quelques informations pour finaliser votre réservation.\n\nPourriez-vous nous préciser :\n{custom_note}\n\nMerci de nous répondre rapidement afin de confirmer votre créneau.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'infos_manquantes','lang'=>'en','title'=>'Missing information',
         'msg'=>"Hello {name},\n\nWe received your request (Ref. {booking_reference}), but we need some additional information to finalise your booking.\n\nCould you please provide:\n{custom_note}\n\nPlease reply as soon as possible to confirm your slot.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'infos_manquantes','lang'=>'ar','title'=>'معلومات ناقصة',
         'msg'=>"مرحباً {name},\n\nلقد استلمنا طلبكم (المرجع: {booking_reference}), لكن تنقصنا بعض المعلومات لإتمام الحجز.\n\nهل يمكنكم تزويدنا بـ:\n{custom_note}\n\nيرجى الرد في أقرب وقت لتأكيد موعدكم.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Tarif proposé ──────────────────────────────────────────────────────
        ['key'=>'tarif_propose','lang'=>'fr','title'=>'Tarif proposé',
         'msg'=>"Bonjour {name},\n\nNous avons étudié votre demande (Réf. {booking_reference}) et voici le tarif proposé pour votre service :\n\n📅 Date : *{date}*\n⏰ Heure : *{start_time}* ({duration})\n👶 Enfants : *{children_count}*\n💰 Tarif : *{price} DH*\n\n{custom_note}\n\nSi ce tarif vous convient, nous procéderons à la confirmation après réception du paiement.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'tarif_propose','lang'=>'en','title'=>'Proposed price',
         'msg'=>"Hello {name},\n\nWe have reviewed your request (Ref. {booking_reference}) and here is the proposed price for your service:\n\n📅 Date: *{date}*\n⏰ Time: *{start_time}* ({duration})\n👶 Children: *{children_count}*\n💰 Price: *{price} DH*\n\n{custom_note}\n\nIf you agree, we will confirm your booking upon receipt of payment.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'tarif_propose','lang'=>'ar','title'=>'السعر المقترح',
         'msg'=>"مرحباً {name},\n\nلقد راجعنا طلبكم (المرجع: {booking_reference}) وإليكم السعر المقترح للخدمة:\n\n📅 التاريخ: *{date}*\n⏰ الوقت: *{start_time}* ({duration})\n👶 الأطفال: *{children_count}*\n💰 السعر: *{price} DH*\n\n{custom_note}\n\nإذا وافقتم، سنؤكد الحجز بعد استلام الدفع.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Demande de paiement ────────────────────────────────────────────────
        ['key'=>'demande_paiement','lang'=>'fr','title'=>'Demande de paiement',
         'msg'=>"Bonjour {name},\n\nVotre demande (Réf. {booking_reference}) est presque confirmée ! Il ne reste plus qu'à effectuer le règlement :\n\n💰 Montant : *{price} DH*\n🏦 Banque : {bank_details}\n\nMerci d'envoyer la preuve de virement via ce lien ou directement sur WhatsApp.\n📎 {payment_link}\n\nDès validation, votre réservation sera confirmée.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'demande_paiement','lang'=>'en','title'=>'Payment request',
         'msg'=>"Hello {name},\n\nYour request (Ref. {booking_reference}) is almost confirmed! Please proceed with payment:\n\n💰 Amount: *{price} DH*\n🏦 Bank: {bank_details}\n\nPlease send proof of payment via this link or directly on WhatsApp.\n📎 {payment_link}\n\nOnce validated, your booking will be confirmed.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'demande_paiement','lang'=>'ar','title'=>'طلب الدفع',
         'msg'=>"مرحباً {name},\n\nطلبكم (المرجع: {booking_reference}) أوشك على التأكيد! يرجى إتمام الدفع:\n\n💰 المبلغ: *{price} DH*\n🏦 البنك: {bank_details}\n\nيرجى إرسال إثبات التحويل عبر هذا الرابط أو مباشرة على واتساب.\n📎 {payment_link}\n\nبعد التحقق، سيتم تأكيد حجزكم.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Paiement reçu ──────────────────────────────────────────────────────
        ['key'=>'paiement_recu','lang'=>'fr','title'=>'Paiement reçu',
         'msg'=>"Bonjour {name},\n\nNous avons bien reçu votre preuve de paiement pour la réservation Réf. {booking_reference}.\n\nNous procédons à la vérification et reviendrons vers vous dès confirmation.\n\nMerci de votre confiance.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'paiement_recu','lang'=>'en','title'=>'Payment received',
         'msg'=>"Hello {name},\n\nWe have received your payment proof for booking Ref. {booking_reference}.\n\nWe are verifying it and will get back to you upon confirmation.\n\nThank you for your trust.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'paiement_recu','lang'=>'ar','title'=>'تم استلام الدفع',
         'msg'=>"مرحباً {name},\n\nلقد استلمنا إثبات دفعكم للحجز المرجع {booking_reference}.\n\nنحن نتحقق منه وسنعود إليكم عند التأكيد.\n\nشكراً لثقتكم.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Réservation confirmée ──────────────────────────────────────────────
        ['key'=>'reservation_confirmee','lang'=>'fr','title'=>'Réservation confirmée',
         'msg'=>"Bonjour {name} ✅\n\nVotre réservation est officiellement *confirmée* !\n\n📋 Réf. : *{booking_reference}*\n📅 Date : *{date}*\n⏰ Heure : *{start_time}* ({duration})\n📍 Lieu : *{hotel}*\n👶 Enfants : *{children_count}*\n💰 Montant : *{price} DH*\n\nUne intervenante qualifiée sera à votre disposition à l'heure convenue.\n\nPour toute question : répondez à ce message.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'reservation_confirmee','lang'=>'en','title'=>'Booking confirmed',
         'msg'=>"Hello {name} ✅\n\nYour booking is officially *confirmed*!\n\n📋 Ref.: *{booking_reference}*\n📅 Date: *{date}*\n⏰ Time: *{start_time}* ({duration})\n📍 Location: *{hotel}*\n👶 Children: *{children_count}*\n💰 Amount: *{price} DH*\n\nA qualified babysitter will be available at the agreed time.\n\nFor any questions, reply to this message.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'reservation_confirmee','lang'=>'ar','title'=>'تأكيد الحجز',
         'msg'=>"مرحباً {name} ✅\n\nحجزكم مؤكد رسمياً!\n\n📋 المرجع: *{booking_reference}*\n📅 التاريخ: *{date}*\n⏰ الوقت: *{start_time}* ({duration})\n📍 المكان: *{hotel}*\n👶 الأطفال: *{children_count}*\n💰 المبلغ: *{price} DH*\n\nستكون جليسة مؤهلة في خدمتكم في الوقت المحدد.\n\nلأي استفسار، ردوا على هذه الرسالة.\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Rappel avant service ───────────────────────────────────────────────
        ['key'=>'rappel_service','lang'=>'fr','title'=>'Rappel avant service',
         'msg'=>"Bonjour {name} 👋\n\nCeci est un rappel pour votre service de babysitting *demain* !\n\n📅 Date : *{date}*\n⏰ Heure : *{start_time}*\n📍 Lieu : *{hotel}*\n\nSi vous avez des instructions particulières pour l'intervenante, n'hésitez pas à nous en informer.\n\nÀ demain !\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'rappel_service','lang'=>'en','title'=>'Service reminder',
         'msg'=>"Hello {name} 👋\n\nThis is a reminder for your babysitting service *tomorrow*!\n\n📅 Date: *{date}*\n⏰ Time: *{start_time}*\n📍 Location: *{hotel}*\n\nIf you have any special instructions for the babysitter, please let us know.\n\nSee you tomorrow!\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'rappel_service','lang'=>'ar','title'=>'تذكير بالخدمة',
         'msg'=>"مرحباً {name} 👋\n\nهذا تذكير بخدمة الجليسة *غداً*!\n\n📅 التاريخ: *{date}*\n⏰ الوقت: *{start_time}*\n📍 المكان: *{hotel}*\n\nإذا كان لديكم تعليمات خاصة للجليسة، يرجى إعلامنا.\n\nإلى الغد!\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Babysitter en route ────────────────────────────────────────────────
        ['key'=>'baby_en_route','lang'=>'fr','title'=>'Babysitter en route',
         'msg'=>"Bonjour {name} 🚗\n\nBonne nouvelle ! Votre intervenante *{babysitter_name}* est en route et arrivera très prochainement.\n\nEn cas de besoin urgent, contactez-nous directement.\n\nBonne journée !\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'baby_en_route','lang'=>'en','title'=>'Babysitter on the way',
         'msg'=>"Hello {name} 🚗\n\nGreat news! Your babysitter *{babysitter_name}* is on her way and will arrive shortly.\n\nIn case of urgent need, please contact us directly.\n\nHave a great day!\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'baby_en_route','lang'=>'ar','title'=>'الجليسة في الطريق',
         'msg'=>"مرحباً {name} 🚗\n\nأخبار سعيدة! جليستكم *{babysitter_name}* في طريقها وستصل قريباً.\n\nفي حالة الطوارئ، تواصلوا معنا مباشرة.\n\nيوماً سعيداً!\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Service terminé ────────────────────────────────────────────────────
        ['key'=>'service_termine','lang'=>'fr','title'=>'Service terminé',
         'msg'=>"Bonjour {name},\n\nNous espérons que le service s'est bien déroulé ! 😊\n\nVotre prestation de babysitting (Réf. {booking_reference}) est maintenant terminée.\n\nN'hésitez pas à partager votre expérience ou à nous faire part de vos commentaires. Votre avis nous aide à améliorer notre service.\n\nMerci et à très bientôt !\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'service_termine','lang'=>'en','title'=>'Service completed',
         'msg'=>"Hello {name},\n\nWe hope the service went well! 😊\n\nYour babysitting session (Ref. {booking_reference}) is now complete.\n\nFeel free to share your experience or send us your feedback. Your opinion helps us improve our service.\n\nThank you and see you soon!\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'service_termine','lang'=>'ar','title'=>'انتهاء الخدمة',
         'msg'=>"مرحباً {name},\n\nنأمل أن تكون الخدمة قد سارت بشكل جيد! 😊\n\nجلسة الجليسة الخاصة بكم (المرجع: {booking_reference}) قد اكتملت.\n\nلا تترددوا في مشاركة تجربتكم أو إرسال ملاحظاتكم. رأيكم يساعدنا على تحسين خدمتنا.\n\nشكراً ونراكم قريباً!\n\n_Faiza Kids Concierge_ 🌿"],

        // ── Annulation ─────────────────────────────────────────────────────────
        ['key'=>'annulation','lang'=>'fr','title'=>'Annulation',
         'msg'=>"Bonjour {name},\n\nNous accusons réception de l'annulation de votre réservation (Réf. {booking_reference}).\n\n{custom_note}\n\nNous espérons pouvoir vous accueillir prochainement.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'annulation','lang'=>'en','title'=>'Cancellation',
         'msg'=>"Hello {name},\n\nWe acknowledge the cancellation of your booking (Ref. {booking_reference}).\n\n{custom_note}\n\nWe hope to welcome you again soon.\n\n_Faiza Kids Concierge_ 🌿"],
        ['key'=>'annulation','lang'=>'ar','title'=>'الإلغاء',
         'msg'=>"مرحباً {name},\n\nنؤكد استلام إلغاء حجزكم (المرجع: {booking_reference}).\n\n{custom_note}\n\nنأمل أن نستقبلكم مجدداً في القريب العاجل.\n\n_Faiza Kids Concierge_ 🌿"],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `whatsapp_templates` (template_key,language,title,message,sort_order) VALUES (?,?,?,?,?)");
    $order = 0;
    foreach ($templates as $t) {
        $stmt->execute([$t['key'], $t['lang'], $t['title'], $t['msg'], $order++]);
    }
    $messages[] = "✓ Templates WhatsApp insérés (" . count($templates) . " templates).";
}

function seed_qr_links(PDO $pdo, array &$messages): void {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `qr_links`");
    if ((int)$stmt->fetchColumn() > 0) return;

    $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

    $links = [
        ['name'=>'Fairmont Taghazout Bay','type'=>'hotel','url'=>$base_url.'/hotel/fairmont-taghazout-bay?source=qr-fairmont','source_param'=>'qr-fairmont'],
        ['name'=>'Hilton Taghazout Bay','type'=>'hotel','url'=>$base_url.'/hotel/hilton-taghazout-bay?source=qr-hilton','source_param'=>'qr-hilton'],
        ['name'=>'Sofitel Royal Bay','type'=>'hotel','url'=>$base_url.'/hotel/sofitel-royal-bay?source=qr-sofitel','source_param'=>'qr-sofitel'],
        ['name'=>'City / Clients externes','type'=>'city','url'=>$base_url.'/city?source=qr-city','source_param'=>'qr-city'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `qr_links` (name,type,url,source_param) VALUES (?,?,?,?)");
    foreach ($links as $l) {
        $stmt->execute([$l['name'],$l['type'],$l['url'],$l['source_param']]);
    }
    $messages[] = "✓ Liens QR par défaut insérés.";
}

/**
 * Run a quick diagnostic check of the environment
 */
function run_diagnostics(PDO $pdo = null): array {
    $results = [];

    // PHP version
    $results['php_version'] = [
        'label'  => 'PHP Version',
        'value'  => PHP_VERSION,
        'ok'     => version_compare(PHP_VERSION, '8.0.0', '>='),
        'msg'    => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'PHP 8+ détecté.' : 'PHP 8.0+ requis.',
    ];

    // Required extensions
    foreach (['pdo_mysql','gd','fileinfo','json','mbstring','openssl'] as $ext) {
        $ok = extension_loaded($ext);
        $results["ext_$ext"] = [
            'label' => "Extension $ext",
            'ok'    => $ok,
            'msg'   => $ok ? "Chargée." : "Manquante — requis.",
        ];
    }

    // DB connection
    if ($pdo) {
        try {
            $pdo->query("SELECT 1");
            $results['db'] = ['label'=>'Connexion MySQL','ok'=>true,'msg'=>'Connectée.'];
        } catch (Exception $e) {
            $results['db'] = ['label'=>'Connexion MySQL','ok'=>false,'msg'=>'Erreur : '.$e->getMessage()];
        }
    }

    // Uploads directory
    $uploads = dirname(__DIR__) . '/uploads';
    $writable = is_dir($uploads) && is_writable($uploads);
    $results['uploads'] = [
        'label' => 'Dossier uploads',
        'ok'    => $writable,
        'msg'   => $writable ? 'Accessible en écriture.' : 'Créer le dossier uploads/ et le rendre accessible en écriture (chmod 755).',
    ];

    // Config file
    $config = dirname(__DIR__) . '/config.php';
    $results['config'] = [
        'label' => 'Fichier config.php',
        'ok'    => file_exists($config),
        'msg'   => file_exists($config) ? 'Présent.' : 'Absent — lancez l\'installeur.',
    ];

    return $results;
}
