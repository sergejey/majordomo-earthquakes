<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['eq_events_qry'];
  } else {
   $session->data['eq_events_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $filter = gr('filter');
  if (!$filter) $filter='all';

  if ($filter == 'strong') {
      $qry.=" AND eq_events.MAGNITUDE>=4";
  } elseif ($filter == 'near') {
      $qry.=" AND PLACE_ID!=0";
  }
  $out['FILTER'] = $filter;

  $sortby_eq_events="eq_events.ADDED DESC";
  $out['SORTBY']=$sortby_eq_events;
  // SEARCH RESULTS

$res_total=SQLSelectOne("SELECT COUNT(*) as TOTAL FROM eq_events WHERE $qry");
require(DIR_MODULES.$this->name.'/Paginator.php');
$page=gr('page','int');
if (!$page) $page=1;
$on_page=10;
$limit=(($page-1)*$on_page).','.$on_page;
$urlPattern='?page=(:num)&filter='.$filter;
$paginator = new JasonGrimes\Paginator($res_total['TOTAL'], $on_page, $page, $urlPattern);
$out['PAGINATOR']=$paginator;


  $res=SQLSelect("SELECT eq_events.*, eq_places.TITLE as PLACE,eq_places.LAT as PLACE_LAT, eq_places.LON as PLACE_LON FROM eq_events LEFT JOIN eq_places ON eq_events.PLACE_ID=eq_places.ID WHERE $qry ORDER BY ".$sortby_eq_events." LIMIT $limit");
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
       $tm = strtotime($res[$i]['ADDED']);
       $diff = time()-$tm;
       if ($diff<20) {
           $res[$i]['DIFF'] = 'Just now';
       } elseif ($diff<60) {
           $res[$i]['DIFF'] = round($diff/(60)).' seconds ago';;
       } elseif ($diff<60*60) {
           $res[$i]['DIFF'] = round($diff/(60)).' minutes ago';;
       } elseif ($diff<24*60*60) {
           $res[$i]['DIFF'] = round($diff/(60*60)).' hours ago';;
       } else {
           $res[$i]['DIFF']=round($diff/(24*60*60)).' days ago';
       }
       if ($res[$i]['PLACE_ID']) {
           $distance = $this->calculateTheDistance($res[$i]['LAT'], $res[$i]['LON'],$res[$i]['PLACE_LAT'],$res[$i]['PLACE_LON']);
           $res[$i]['DISTANCE'] = round($distance/1000);
       }
   }
   $out['RESULT']=$res;
  }
