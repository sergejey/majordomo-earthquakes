<?php
/**
 * Earthquakes
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 11:02:40 [Feb 18, 2023])
 */
//
//
class earthquakes extends module
{
    /**
     * earthquakes
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "earthquakes";
        $this->title = "Earthquakes";
        $this->module_category = "<#LANG_SECTION_APPLICATIONS#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (isset($this->id)) {
            $p["id"] = $this->id;
        }
        if (isset($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (isset($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (isset($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (isset($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (isset($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        $this->getConfig();


        $this->data_source = gr('data_source');

        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        $out['DATA_SOURCE'] = $this->data_source;

        if ($this->data_source == 'places') {
            if ($this->view_mode == '' || $this->view_mode == 'search_eq_places') {
                $this->search_eq_places($out);
            }
            if ($this->view_mode == 'edit_eq_places') {
                $this->edit_eq_places($out, $this->id);
            }
        }

        if ($this->data_source == 'eq_events' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_eq_events') {
                $this->search_eq_events($out);
            }
            if ($this->view_mode == 'edit_eq_events') {
                $this->edit_eq_events($out, $this->id);
            }
            if ($this->view_mode == 'delete_eq_events') {
                $this->delete_eq_events($this->id);
                $this->redirect("?");
            }
        }
    }

    function search_eq_places(&$out)
    {
        $result = SQLSelect("SELECT * FROM eq_places ORDER BY TITLE");
        $out['RESULT'] = $result;
    }

    function edit_eq_places(&$out, $id)
    {
        $rec = SQLSelectOne("SELECT * FROM eq_places WHERE ID=" . (int)$id);
        if ($this->mode == 'update') {
            $ok = 1;
            $rec['TITLE'] = gr('title');
            if ($rec['TITLE'] == '') {
                $out['ERR_TITLE'] = 1;
                $ok = 0;
            }
            $rec['LAT'] = gr('lat');
            if ($rec['LAT'] == '') {
                $out['ERR_LAT'] = 1;
                $ok = 0;
            }

            $rec['LON'] = gr('lon');
            if ($rec['LON'] == '') {
                $out['ERR_LON'] = 1;
                $ok = 0;
            }

            $rec['RADIUS'] = gr('radius', 'int');
            if ($rec['RADIUS'] == '') {
                $out['ERR_RADIUS'] = 1;
                $ok = 0;
            }

            $rec['MIN_MAGNITUDE'] = gr('min_magnitude', 'int');
            $rec['ALERT_MESSAGE'] = gr('alert_message');
            $rec['ALERT_LEVEL'] = gr('alert_level', 'int');
            $rec['LINKED_OBJECT'] = gr('linked_object');
            $rec['LINKED_METHOD'] = gr('linked_method');

            if ($ok) {
                if ($rec['ID']) {
                    SQLUpdate('eq_places', $rec); // update
                } else {
                    $rec['ID'] = SQLInsert('eq_places', $rec); // adding new record
                }
                $out['OK'] = 1;
            } else {
                $out['ERR'] = 1;
            }
        }
        outHash($rec, $out);
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    function api($params)
    {
        if ($params['ev_unid']) {
            $event = SQLSelectOne("SELECT ID FROM eq_events WHERE EV_UNID='" . $params['ev_unid'] . "'");
            if ($event['ID']) return;
            $event = array();
            $event['EV_UNID'] = $params['ev_unid'];
            $event['LAT'] = $params['ev_latitude'];
            $event['LON'] = $params['ev_longitude'];
            $event['MAGNITUDE'] = $params['ev_mag_value'];
            $event['ADDED'] = date('Y-m-d H:i:s', $params['ev_event_time']);
            $event['TITLE'] = $params['mt_region'];
            $event['ID'] = SQLInsert('eq_events', $event);

            $places = SQLSelect("SELECT * FROM eq_places");
            $total = count($places);
            for ($i = 0; $i < $total; $i++) {
                $place_magnitude = $places[$i]['MIN_MAGNITUDE'];
                if ($place_magnitude > $event['MAGNITUDE']) {
                    //DebMes("skipping ".$event['TITLE'].": ".$event['MAGNITUDE']." < $place_magnitude",'earthquakes');
                    continue;
                }
                $place_lat = $places[$i]['LAT'];
                $place_lon = $places[$i]['LON'];

                $distance = round($this->calculateTheDistance($place_lat, $place_lon, $event['LAT'], $event['LON']) / 1000, 2);
                $place_radius = $places[$i]['RADIUS'];
                if ($distance <= $place_radius) {
                    DebMes("alert ".$event['TITLE'].": $distance < $place_radius",'earthquakes');

                    $event['PLACE_ID'] = $places[$i]['ID'];
                    SQLUpdate('eq_events',$event);

                    $time_passed = time()-strtotime($event['ADDED']); // seconds
                    if ($time_passed > 30 * 60) { // если старше 30 минут, то не интересно
                        continue;
                    }
                    //todo: реагировать на событие один раз а не несколько раз если попадает несколько мест

                    SQLExec("UPDATE eq_places SET UPDATED='".date('Y-m-d H:i:s')."' WHERE ID=".$places[$i]['ID']);

                    if ($places[$i]['ALERT_MESSAGE']) {
                        say($places[$i]['ALERT_MESSAGE'], $places[$i]['ALERT_LEVEL']);
                    }
                    if ($places[$i]['LINKED_OBJECT'] && $places[$i]['LINKED_METHOD']) {
                        callMethod($places[$i]['LINKED_OBJECT'].'.'.$places[$i]['LINKED_METHOD']);
                    }
                } else {
                    //DebMes("no alert ".$event['TITLE'].": $distance > $place_radius",'earthquakes');
                }
            }

            // cleanup
            SQLExec("DELETE FROM eq_events WHERE PLACE_ID=0 AND (TO_DAYS(NOW())-TO_DAYS(ADDED))>7");

        }


    }

    function calculateTheDistance($latA, $lonA, $latB, $lonB)
    {
        define('EARTH_RADIUS', 6372795);

        $lat1 = $latA * M_PI / 180;
        $lat2 = $latB * M_PI / 180;
        $long1 = $lonA * M_PI / 180;
        $long2 = $lonB * M_PI / 180;

        $cl1 = cos($lat1);
        $cl2 = cos($lat2);
        $sl1 = sin($lat1);
        $sl2 = sin($lat2);

        $delta = $long2 - $long1;
        $cdelta = cos($delta);
        $sdelta = sin($delta);

        $y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
        $x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;

        $ad = atan2($y, $x);

        $dist = round($ad * EARTH_RADIUS);

        return $dist;
    }

    /**
     * eq_events search
     *
     * @access public
     */
    function search_eq_events(&$out)
    {
        require(dirname(__FILE__) . '/eq_events_search.inc.php');
    }

    /**
     * eq_events edit/add
     *
     * @access public
     */
    function edit_eq_events(&$out, $id)
    {
        require(dirname(__FILE__) . '/eq_events_edit.inc.php');
    }

    /**
     * eq_events delete record
     *
     * @access public
     */
    function delete_eq_events($id)
    {
        $rec = SQLSelectOne("SELECT * FROM eq_events WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM eq_events WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS eq_events');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        eq_events -
        */
        $data = <<<EOD
 eq_events: ID int(10) unsigned NOT NULL auto_increment
 eq_events: TITLE varchar(255) NOT NULL DEFAULT ''
 eq_events: EV_UNID varchar(255) NOT NULL DEFAULT ''
 eq_events: LAT float DEFAULT '0' NOT NULL
 eq_events: LON float DEFAULT '0' NOT NULL
 eq_events: MAGNITUDE float NOT NULL DEFAULT '0'
 eq_events: PLACE_ID int(10) NOT NULL DEFAULT '0'
 eq_events: ADDED datetime
 
 eq_places: ID int(10) unsigned NOT NULL auto_increment
 eq_places: TITLE varchar(255) NOT NULL DEFAULT ''
 eq_places: LAT float DEFAULT '0' NOT NULL
 eq_places: LON float DEFAULT '0' NOT NULL
 eq_places: RADIUS int(10) NOT NULL DEFAULT '0'
 eq_places: MIN_MAGNITUDE float NOT NULL DEFAULT '0'
 eq_places: ALERT_MESSAGE varchar(255) NOT NULL DEFAULT ''
 eq_places: ALERT_LEVEL int(3) NOT NULL DEFAULT '0'
 eq_places: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 eq_places: LINKED_METHOD varchar(255) NOT NULL DEFAULT ''
 eq_places: UPDATED datetime
 
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRmViIDE4LCAyMDIzIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
