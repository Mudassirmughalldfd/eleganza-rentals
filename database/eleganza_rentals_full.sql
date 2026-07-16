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

-- Initial Eleganza Rentals data
START TRANSACTION;
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('site_name','Eleganza Rentals','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('phone','07728 393135','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('phone_link','07728393135','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('email','hello@eleganzarentals.co.uk','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('whatsapp','447728393135','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('hero_kicker','Italian Style • Premium Experience','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('hero_title_top','DRIVE','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('hero_title_bottom','EXTRAORDINARY','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('hero_text','Premium car hire for every journey. Luxury, performance and prestige — delivered with Italian style.','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('about_intro','Eleganza Rentals is built for drivers who expect more than transportation. We combine premium vehicles, attentive communication and a polished rental experience with a distinctly Italian sense of style.','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('footer_line','Elegance in every detail. Excellence on every road.','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_enabled','0','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_host','smtp.hostinger.com','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_port','587','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_encryption','tls','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_username','hello@eleganzarentals.co.uk','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_password','','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_from_email','hello@eleganzarentals.co.uk','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('smtp_from_name','Eleganza Rentals','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('notification_email','hello@eleganzarentals.co.uk','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('auto_reply_enabled','1','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('notification_subject','New Eleganza Rentals enquiry: {{vehicle}}','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('auto_reply_subject','We received your Eleganza Rentals enquiry','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO site_settings (setting_key,setting_value,updated_at) VALUES ('auto_reply_body','<p>Hello {{name}},</p><p>Thank you for contacting Eleganza Rentals. We have received your enquiry for <strong>{{vehicle}}</strong> and will respond as soon as possible.</p><p>Eleganza Rentals<br>07728 393135</p>','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=VALUES(updated_at);
INSERT INTO admins (id,name,email,password_hash,created_at,updated_at) VALUES ('admin_primary','Eleganza Administrator','admin@eleganzarentals.co.uk','$2y$12$Uy161.mh.Rw3Z4stJBi73.SxteE5bTWrZjyRB52K2pfKBwpCNEsvO','2026-07-16 09:13:58','2026-07-16 09:13:58') ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),password_hash=VALUES(password_hash),updated_at=VALUES(updated_at);
INSERT INTO cars (id,make,model,year,slug,starting_price,short_description,description,over_seven,published,featured,sort_order,default_status,public_note,created_at,updated_at) VALUES ('car_mercedes','MERCEDES-BENZ','AMG A 35 PREMIUM + 4MATIC EDITION','2022','mercedes-benz-amg-a35-premium-4matic','300','AMG performance, premium comfort and confident 4MATIC road presence.','A premium compact performance vehicle combining distinctive AMG styling, refined comfort and confident all-weather capability. Ideal for special occasions, business travel and memorable weekend drives.','Contact us',1,1,1,'available','','2026-07-16 09:13:58','2026-07-16 09:13:58') ON DUPLICATE KEY UPDATE make=VALUES(make),model=VALUES(model),year=VALUES(year),slug=VALUES(slug),starting_price=VALUES(starting_price),short_description=VALUES(short_description),description=VALUES(description),over_seven=VALUES(over_seven),published=VALUES(published),featured=VALUES(featured),sort_order=VALUES(sort_order),default_status=VALUES(default_status),public_note=VALUES(public_note),updated_at=VALUES(updated_at);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',1,300.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',2,500.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',3,700.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',4,900.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',5,1100.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',6,1300.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_mercedes',7,1500.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO cars (id,make,model,year,slug,starting_price,short_description,description,over_seven,published,featured,sort_order,default_status,public_note,created_at,updated_at) VALUES ('car_bmw','BMW','420d XDRIVE','2018','bmw-420d-xdrive','180','Refined coupé styling, premium comfort and confident xDrive capability.','A sophisticated BMW coupé created for comfortable long-distance journeys and stylish city driving. Its elegant profile and composed road manners make every trip feel considered and premium.','Contact us',1,1,2,'available','','2026-07-16 09:13:58','2026-07-16 09:13:58') ON DUPLICATE KEY UPDATE make=VALUES(make),model=VALUES(model),year=VALUES(year),slug=VALUES(slug),starting_price=VALUES(starting_price),short_description=VALUES(short_description),description=VALUES(description),over_seven=VALUES(over_seven),published=VALUES(published),featured=VALUES(featured),sort_order=VALUES(sort_order),default_status=VALUES(default_status),public_note=VALUES(public_note),updated_at=VALUES(updated_at);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',1,180.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',2,290.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',3,400.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',4,510.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',5,620.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',6,730.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO car_prices (car_id,days,price) VALUES ('car_bmw',7,840.0) ON DUPLICATE KEY UPDATE price=VALUES(price);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_image_01','car_mercedes','image','uploads/cars/mercedes/mercedes-01.jpg','','Mercedes-AMG A 35 front three-quarter view','Mercedes-AMG A 35 4MATIC front three-quarter view',1,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_image_02','car_mercedes','image','uploads/cars/mercedes/mercedes-02.jpg','','Mercedes-AMG A 35 rear three-quarter view','Mercedes-AMG A 35 4MATIC rear three-quarter view',2,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_image_03','car_mercedes','image','uploads/cars/mercedes/mercedes-03.jpg','','Mercedes-AMG A 35 hatchback front view','Mercedes-AMG A 35 4MATIC hatchback front view',3,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_image_04','car_mercedes','image','uploads/cars/mercedes/mercedes-04.jpg','','Mercedes-AMG A 35 hatchback rear view','Mercedes-AMG A 35 4MATIC hatchback rear view',4,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_youtube_01','car_mercedes','youtube','https://www.youtube.com/watch?v=5NKPcsrTdKY','uploads/cars/mercedes/mercedes-01.jpg','2022 Mercedes-AMG A 35 — Exterior, Interior & Sound','2022 Mercedes-AMG A 35 exterior interior and sound video',5,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_mercedes_youtube_02','car_mercedes','youtube','https://www.youtube.com/watch?v=G04wzWRH9ME','uploads/cars/mercedes/mercedes-03.jpg','2022 A 35 Premium 4MATIC Edition — Walkaround','2022 Mercedes-AMG A 35 Premium 4MATIC Edition walkaround video',6,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_image_01','car_bmw','image','uploads/cars/bmw/bmw-01.jpg','','BMW 420d M Sport front three-quarter view','BMW 420d M Sport front three-quarter view',1,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_image_02','car_bmw','image','uploads/cars/bmw/bmw-02.jpg','','BMW 4 Series Coupé side and rear view','BMW 4 Series 420d Coupé side and rear view',2,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_image_03','car_bmw','image','uploads/cars/bmw/bmw-03.jpg','','BMW 4 Series Coupé road view','BMW 4 Series 420d Coupé road view',3,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_image_04','car_bmw','image','uploads/cars/bmw/bmw-04.jpg','','BMW 4 Series Coupé profile view','BMW 4 Series 420d Coupé profile view',4,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_youtube_01','car_bmw','youtube','https://www.youtube.com/watch?v=BZWHH9ScmwM','uploads/cars/bmw/bmw-01.jpg','2018 BMW 420d xDrive — Exterior & Interior','2018 BMW 420d xDrive exterior and interior video',5,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO vehicle_media (id,car_id,media_type,media_path,poster_path,title,alt_text,sort_order,created_at) VALUES ('media_bmw_youtube_02','car_bmw','youtube','https://www.youtube.com/watch?v=1V8ep4o3-B8','uploads/cars/bmw/bmw-02.jpg','2018 BMW 420d M Sport — Walkaround','2018 BMW 420d M Sport walkaround video',6,'2026-07-16 12:00:00') ON DUPLICATE KEY UPDATE car_id=VALUES(car_id),media_type=VALUES(media_type),media_path=VALUES(media_path),poster_path=VALUES(poster_path),title=VALUES(title),alt_text=VALUES(alt_text),sort_order=VALUES(sort_order);
INSERT INTO app_meta (meta_key,meta_value,updated_at) VALUES ('schema_version','1.0.0','2026-07-16 10:52:10') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value),updated_at=VALUES(updated_at);
COMMIT;
