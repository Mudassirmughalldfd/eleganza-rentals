SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS admins (
    id VARCHAR(64) NOT NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(191) NOT NULL,
    setting_value LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cars (
    id VARCHAR(64) NOT NULL,
    make VARCHAR(160) NOT NULL,
    model VARCHAR(255) NOT NULL,
    year VARCHAR(8) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    starting_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    short_description TEXT NULL,
    description LONGTEXT NULL,
    over_seven VARCHAR(191) NOT NULL DEFAULT 'Contact us',
    published TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 99,
    default_status VARCHAR(40) NOT NULL DEFAULT 'available',
    public_note TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_car_slug (slug),
    KEY idx_cars_published_sort (published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS car_prices (
    car_id VARCHAR(64) NOT NULL,
    days TINYINT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (car_id, days),
    CONSTRAINT fk_car_prices_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicle_media (
    id VARCHAR(64) NOT NULL,
    car_id VARCHAR(64) NOT NULL,
    media_type VARCHAR(30) NOT NULL DEFAULT 'image',
    media_path TEXT NOT NULL,
    poster_path TEXT NULL,
    title VARCHAR(255) NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 99,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_media_car_sort (car_id, sort_order),
    CONSTRAINT fk_vehicle_media_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability_blocks (
    id VARCHAR(64) NOT NULL,
    car_id VARCHAR(64) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'unavailable',
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    public_note TEXT NULL,
    private_note TEXT NULL,
    show_return TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_availability_car_dates (car_id, start_at, end_at),
    CONSTRAINT fk_availability_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
    id VARCHAR(64) NOT NULL,
    source VARCHAR(60) NOT NULL DEFAULT 'contact_page',
    subject VARCHAR(191) NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    contact_method VARCHAR(80) NULL,
    vehicle_id VARCHAR(64) NULL,
    vehicle_name VARCHAR(255) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    message LONGTEXT NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'new',
    admin_notes LONGTEXT NULL,
    mail_status TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_enquiries_status_created (status, created_at),
    KEY idx_enquiries_source (source),
    KEY idx_enquiries_vehicle (vehicle_id),
    CONSTRAINT fk_enquiries_vehicle FOREIGN KEY (vehicle_id) REFERENCES cars(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id VARCHAR(64) NOT NULL,
    action VARCHAR(191) NOT NULL,
    detail TEXT NULL,
    admin_email VARCHAR(191) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_activity_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_meta (
    meta_key VARCHAR(191) NOT NULL,
    meta_value LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
