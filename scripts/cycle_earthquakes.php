<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'earthquakes/earthquakes.class.php');
$earthquakes_module = new earthquakes();
$earthquakes_module->getConfig();

echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;

$checkEvery = 30;

$seenEvents = array();

while (1) {
    setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    if ((time() - $latest_check) > $checkEvery) {
        $latest_check = time();
        $content = getURL('https://www.seismicportal.eu/fdsnws/event/1/query?limit=10&format=json');
        if ($content != '') {
            $data = json_decode($content, true);
            $data = $data['features'];
            if (is_array($data)) {
                $total = count($data);
                for ($i = 0; $i < $total; $i++) {
                    $ev_unid = $data[$i]['properties']['unid'];
                    if (isset($seenEvents[$ev_unid])) continue;
                    $ev_longitude = $data[$i]['properties']['lon'];
                    $ev_latitude = $data[$i]['properties']['lat'];
                    $ev_mag_value = $data[$i]['properties']['mag'];
                    $mt_region = $data[$i]['properties']['flynn_region'];
                    $ev_event_time = strtotime($data[$i]['properties']['time']);
                    echo "$ev_unid $mt_region ($ev_latitude : $ev_longitude) $ev_mag_value " . date('Y-m-d H:i:s', $ev_event_time) . "\n";

                    callAPI('/api/module/earthquakes', 'GET', array(
                            'ev_unid' => $ev_unid,
                            'ev_latitude' => $ev_latitude,
                            'ev_longitude' => $ev_longitude,
                            'ev_mag_value' => $ev_mag_value,
                            'ev_event_time' => $ev_event_time,
                            'mt_region' => $mt_region
                        )
                    );

                    $seenEvents[$ev_unid] = 1;
                }
            }
        }
    }
    if (file_exists('./reboot') || isset($_GET['onetime'])) {
        $db->Disconnect();
        exit;
    }
    sleep(5);
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
