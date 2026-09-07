-- ============================================================
-- Схема БД: Запись на приём к психологам вуза
-- MySQL / MariaDB, InnoDB, utf8mb4
-- ============================================================

-- Явно фиксируем кодировку соединения клиента. Без этой строки
-- Docker-контейнер MySQL (docker-entrypoint-initdb.d) импортирует файл
-- через клиент с дефолтной локалью контейнера (часто не UTF-8) —
-- кириллица превращается в кракозябры, даже если у самой БД
-- character-set-server=utf8mb4. При обычном ручном импорте эту же
-- проблему лечили флагом --default-character-set=utf8mb4 у команды
-- mysql, но Docker сам решает, как запускать импорт, поэтому кодировку
-- надёжнее зашить в сам файл.
SET NAMES utf8mb4;

-- CREATE DATABASE IF NOT EXISTS psycho_booking
--     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE psycho_booking;

-- ------------------------------------------------------------
-- Пользователи: ровно две роли — student и admin
-- ------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role            ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    last_name       VARCHAR(100) NOT NULL,
    first_name      VARCHAR(100) NOT NULL,
    patronymic      VARCHAR(100) NULL,
    group_or_dept   VARCHAR(100) NULL COMMENT 'группа студента / кафедра ППС / отдел сотрудника',
    phone           VARCHAR(20)  NULL,
    email           VARCHAR(150) NULL UNIQUE,
    contact_link    VARCHAR(255) NULL COMMENT 'ссылка для связи (ВК, телеграм и т.п.)',
    password_hash   VARCHAR(255) NULL COMMENT 'NULL пока нет своей авторизации / если вход через SSO портала',
    telegram_chat_id VARCHAR(64) NULL COMMENT 'привязывается через бота (/link команда), NULL пока не привязан',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Профиль психолога (доп. данные поверх users)
-- В системе психолог — сотрудник с ролью admin.
-- ------------------------------------------------------------
CREATE TABLE psychologist_profiles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    photo_path      VARCHAR(255) NULL,
    bio             TEXT NULL,
    max_hours_per_week SMALLINT UNSIGNED NOT NULL DEFAULT 9,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Справочник направлений психологической помощи
CREATE TABLE directions (
    id      TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO directions (name) VALUES
    ('Консультирование'), ('Коррекция'), ('Профилактика'),
    ('Диагностика'), ('Просвещение');

-- Связь психолог <-> направления (многие-ко-многим)
CREATE TABLE psychologist_directions (
    psychologist_id INT UNSIGNED NOT NULL,
    direction_id    TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (psychologist_id, direction_id),
    FOREIGN KEY (psychologist_id) REFERENCES psychologist_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (direction_id) REFERENCES directions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Слоты расписания
-- ------------------------------------------------------------
CREATE TABLE schedule_slots (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    psychologist_id INT UNSIGNED NOT NULL,
    slot_date       DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    format          ENUM('individual', 'group', 'family') NOT NULL DEFAULT 'individual',
    status          ENUM('free', 'booked', 'blocked') NOT NULL DEFAULT 'free',
    is_emergency_override BOOLEAN NOT NULL DEFAULT FALSE
                    COMMENT 'слот добавлен сверх недельного лимита 9ч — экстренный случай',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (psychologist_id) REFERENCES psychologist_profiles(id) ON DELETE CASCADE,
    INDEX idx_slot_lookup (psychologist_id, slot_date, status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Записи
-- ------------------------------------------------------------
CREATE TABLE appointments (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_id            INT UNSIGNED NOT NULL UNIQUE,
    client_id          INT UNSIGNED NOT NULL,
    request_comment    VARCHAR(500) NULL COMMENT 'краткий запрос клиента, по желанию',
    status             ENUM('active', 'cancelled', 'completed', 'rescheduled') NOT NULL DEFAULT 'active',
    rescheduled_to_id  INT UNSIGNED NULL COMMENT 'на какую запись перенесли (если status = rescheduled)',
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at       DATETIME NULL,
    FOREIGN KEY (slot_id) REFERENCES schedule_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rescheduled_to_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Заметки психолога к записи (видны только администраторам-психологам)
-- ------------------------------------------------------------
CREATE TABLE appointment_notes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT UNSIGNED NOT NULL,
    note_text       TEXT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Групповые мероприятия / объявления Центра
-- ------------------------------------------------------------
CREATE TABLE announcements (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind            ENUM('event', 'info') NOT NULL DEFAULT 'event',
    title           VARCHAR(200) NOT NULL,
    content         TEXT NOT NULL,
    event_date      DATETIME NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Прочие контакты для связи
-- ------------------------------------------------------------
CREATE TABLE contacts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    value           VARCHAR(255) NOT NULL,
    type            ENUM('phone', 'link', 'text') NOT NULL DEFAULT 'text'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Лог уведомлений
-- ------------------------------------------------------------
CREATE TABLE notification_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    channel         ENUM('email', 'portal', 'telegram') NOT NULL,
    subject         VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    status          ENUM('stub', 'sent', 'failed') NOT NULL DEFAULT 'stub',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
