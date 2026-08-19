<?php

// namespace App\Core;

// use PDO;
// use PDOException;

// /**
//  * Простая обёртка над PDO. Одно подключение на весь запрос (singleton).
//  */
// class Database
// {
//     private static ?PDO $instance = null;

//     public static function connection(): PDO
//     {
//         if (self::$instance === null) {
//             $config = require __DIR__ . '/../../config/config.php';
//             $db = $config['db'];

//             $dsn = sprintf(
//                 'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
//                 $db['host'],
//                 $db['port'],
//                 $db['name']
//             );

//             try {
//                 self::$instance = new PDO($dsn, $db['user'], $db['password'], [
//                     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//                 ]);
//             } catch (PDOException $e) {
//                 // На проде так делать не стоит (утечка деталей), но на этапе
//                 // локальной разработки удобно сразу видеть причину.
//                 die('Ошибка подключения к БД: ' . $e->getMessage());
//             }
//         }

//         return self::$instance;
//     }
// }



namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            // Приоритет: переменные окружения (Docker) → config.php (локально)    | для докера нужна своя сеть, вместо адресов -> имена сервисов
            $host = getenv('DB_HOST') ?: null;
            $port = getenv('DB_PORT') ?: null;
            $name = getenv('DB_NAME') ?: null;
            $user = getenv('DB_USER') ?: null;
            $password = getenv('DB_PASSWORD') ?: null;

            // Если нет переменных окружения — читаем config.php
            if (!$host) {
                $configFile = __DIR__ . '/../../config/config.php';
                if (file_exists($configFile)) {
                    $config = require $configFile;
                    $db = $config['db'];
                    $host = $db['host'] ?? '127.0.0.1';
                    $port = $db['port'] ?? '3306';
                    $name = $db['name'] ?? 'psycho_booking';
                    $user = $db['user'] ?? 'root';
                    $password = $db['password'] ?? '';
                } else {
                    // Дефолтные значения для локальной разработки
                    $host = '127.0.0.1';
                    $port = '3306';
                    $name = 'psycho_booking';
                    $user = 'root';
                    $password = '';
                }
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $name
            );

            try {
                self::$instance = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,    
                    // для кодировки
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",           
                ]);

            } catch (PDOException $e) {
                die('Ошибка подключения к БД: ' . $e->getMessage());
            }
        }
        self::$instance->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
self::$instance->exec("SET CHARACTER SET utf8mb4");
        return self::$instance;
    }
}