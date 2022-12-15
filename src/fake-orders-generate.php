<?php

use Dotenv\Dotenv;
const DEFAULT_QUANTITY = 10;

require_once '../vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->load();
try {
    $dbh = new PDO('mysql:host='.$_ENV['HOST'].';dbname='.$_ENV['DBNAME'], $_ENV['DB_USER'], $_ENV['PASSWORD']);
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $periodInDays = $_GET['--period'];
    $quantity = $_GET['--quantity'];

    $mainQuantity = round($quantity / 3 * 2);

    $sql = 'INSERT INTO wp_posts (post_content, post_title, post_excerpt, to_ping, pinged, post_content_filtered, post_type, post_date)
        values ("post_content", "post_title", "post_excerpt", "to_ping", "pinged", "post_content_filtered", "shop_order", :endDate)';
    $sth = $dbh->prepare($sql);

    function createOrders($quantity, $mainQuantity, $periodInDays, $sth){
        $i = 1;
        while($i <= $quantity) {
            if ($i <= $mainQuantity) {
                $hoursManyOrders = rand(12,17);
                $hours = $hoursManyOrders;
            } else {
                $hoursLittleOrders = rand(0, 17);
                if ($hoursLittleOrders >= 12) {
                    $hoursLittleOrders += 6;
                }
                $hours = $hoursLittleOrders;
            }
            $randPeriodInDays = rand(0,$periodInDays);
            $periodauction = date("Y-m-d H:i:s", strtotime('-'.$randPeriodInDays.'days'));
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $periodauction);
            $date->setTime($hours,0);
            $endDate = $date->format('Y-m-d H:i:s');
            $sth->execute(['endDate' => $endDate]);
            $i++;
        }
    }

    function setInterval($f, $milliseconds)
    {
        $seconds=(int)$milliseconds/1000;
        while(true)
        {
            $f();
            sleep($seconds);
        }
    }

    createOrders($quantity, $mainQuantity, $periodInDays, $sth);


    if (isset($_GET['--quantity-per-minute'])) {
        setInterval(function(){
            global $sth, $periodInDays;
            $quantityPerMinute = $_GET['--quantity-per-minute'];
            if ($quantityPerMinute) {
                $quantity = $quantityPerMinute;
            } else {
                $quantity = DEFAULT_QUANTITY;
            }
            $mainQuantity = round($quantity / 3 * 2);
            createOrders($quantity, $mainQuantity, $periodInDays, $sth);
            }, 60000);
    }


    foreach($dbh->query('SELECT post_date from wp_posts where post_type = "shop_order"') as $row) {
        print_r($row);
    }
    $dbh->query('DELETE from wp_posts');
    $dbh = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

