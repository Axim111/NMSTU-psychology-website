-- Тестовые данные — чтобы видеть реальный флоу записи, а не пустые страницы.
-- Выполнить после schema.sql:
--   mysql -u root psycho_booking < database/seed.sql

-- Та же причина, что и в schema.sql — фиксируем кодировку клиента,
-- иначе Docker-инициализация испортит кириллицу.
SET NAMES utf8mb4;

USE psycho_booking;

-- Тестовый пароль для ВСЕХ аккаунтов ниже (психологи, админ, студенты): password123
-- Это bcrypt-хеш строки "password123", сгенерированный заранее.
SET @test_password_hash = '$2b$12$Sa6Ptq3Nw0F2.cw8xapuFeQyUeYGmn46n6w4fFO9xQS.8wH4keIg2';

-- Два психолога (у обоих теперь есть логин/пароль)
INSERT INTO users (role, last_name, first_name, patronymic, email, password_hash) VALUES
    ('psychologist', 'Филиппова', 'Елена', 'Валерьевна', 'filippova@example.com', @test_password_hash),
    ('psychologist', 'Сергеев', 'Иван', 'Петрович', 'sergeev@example.com', @test_password_hash);
    
-- Администратор
INSERT INTO users (role, last_name, first_name, email, password_hash) VALUES
    ('admin', 'Викторова', 'Виктория', 'admin@example.com', @test_password_hash);

-- Два студента с готовыми аккаунтами (кабинет студента)
INSERT INTO users (role, last_name, first_name, group_or_dept, phone, email, password_hash) VALUES
    ('client', 'Иванов', 'Семён', 'ИВТ-21', '+79990001122', 'student1@example.com', @test_password_hash),
    ('client', 'Петрова', 'Анна', 'ФиИТ-22', '+79990003344', 'student2@example.com', @test_password_hash);

INSERT INTO psychologist_profiles (user_id, bio, max_hours_per_week)
SELECT id, 'Работаю со студентами и сотрудниками вуза, индивидуальные консультации.', 9
FROM users WHERE email = 'filippova@example.com';

INSERT INTO psychologist_profiles (user_id, bio, max_hours_per_week)
SELECT id, 'Специализируюсь на групповой и семейной работе.', 9
FROM users WHERE email = 'sergeev@example.com';

-- Направления психологов (id направлений: 1 Консультирование, 2 Коррекция, 3 Профилактика, 4 Диагностика, 5 Просвещение)
INSERT INTO psychologist_directions (psychologist_id, direction_id)
SELECT p.id, 1 FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO psychologist_directions (psychologist_id, direction_id)
SELECT p.id, 4 FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO psychologist_directions (psychologist_id, direction_id)
SELECT p.id, 2 FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'sergeev@example.com';
INSERT INTO psychologist_directions (psychologist_id, direction_id)
SELECT p.id, 5 FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'sergeev@example.com';

-- Свободные слоты на ближайшие дни для обоих психологов
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 1 DAY, '09:00:00', '09:40:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 1 DAY, '09:40:00', '10:20:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 1 DAY, '10:20:00', '11:00:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 2 DAY, '09:00:00', '09:40:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 2 DAY, '09:40:00', '10:20:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'filippova@example.com';

INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 1 DAY, '13:00:00', '13:40:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'sergeev@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 1 DAY, '13:40:00', '14:20:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'sergeev@example.com';
INSERT INTO schedule_slots (psychologist_id, slot_date, start_time, end_time, format, status)
SELECT p.id, CURDATE() + INTERVAL 3 DAY, '13:00:00', '13:40:00', 'individual', 'free'
FROM psychologist_profiles p JOIN users u ON u.id = p.user_id WHERE u.email = 'sergeev@example.com';

-- Контакты (раздел "Ссылки")
INSERT INTO contacts (title, value, type) VALUES
    ('Телефон экстренной помощи', '8-800-2000-122', 'phone'),
    ('ЦСИ «Пирамиды»', '+7 900 000-00-00', 'phone'),
    ('ВК кафедры психологии', 'https://vk.com/example_dept', 'link');

-- Пример объявления и памятки
INSERT INTO announcements (kind, title, content, event_date, created_by)
SELECT 'event', 'Групповое занятие по стресс-менеджменту',
       'Встречаемся в ауд. 305, регистрация не нужна.', CURDATE() + INTERVAL 5 DAY, u.id
FROM users u WHERE u.email = 'sergeev@example.com';

INSERT INTO announcements (kind, title, content, created_by)
SELECT 'info', 'Правила записи и отмены',
       'Отменить запись можно не позднее чем за 24 часа. При экстренной ситуации звоните на телефон экстренной помощи.', u.id
FROM users u WHERE u.email = 'filippova@example.com';
