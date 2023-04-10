<?php
declare(strict_types=1);
require 'vendor/autoload.php';
use library\DateTime;
use library\AcademicCalendar;
use library\HtmlHelper;
use Symfony\Component\Yaml\Yaml;

date_default_timezone_set("Asia/Tokyo");
$year = (int) (isset($_GET['y']) ? $_GET['y'] : date('Y'));

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="css/cal.css" rel="stylesheet" type="text/css" media="all" />
<title><?=$year?>カレンダー</title>
</head>
<body>

<?php
$ja = Yaml::parseFile('conf/public_holidays.yaml');
$conf = Yaml::parseFile('conf/config.yaml');

$acyear = new AcademicCalendar($year);
$public = $acyear->getPublicHolidays();
try{ 
    $local = Yaml::parseFile('conf/local_schedules/'.$year.'.yaml');
    for ($m=1; $m < 4; $m++){
        $local[$m] = null; // months of last business year
    }
}catch(Exception $e){
    $local = [];
}

try {
    $_loc = Yaml::parseFile('conf/local_schedules/'.($year+1).'.yaml');
    for ($m=1; $m < 4; $m++) // months of this business year
        $local[$m] = isset($_loc[$m]) ? $_loc[$m] : [];
}catch (Exception $e){}

$printMonth = function($month) use ($year, $acyear, $public, $local, $conf, $ja)
{
    $wdays_jp = $conf['weekdays_jp'];
    $wdays_en = $conf['weekdays_en'];
    $h = new HtmlHelper();
    $year = $month < 4 ? $year + 1 : $year; 

    $rs = $h->td()->attr('style', 'vertical-align:top; width:16%');
    $rs->append( $h->div($month . '月')->attr('class', 'month') );
    $tbl = $h->table()->attr('class',"months-table");
    $tr = $h->tr()->attr( 'style', "margin-bottom: 10px;");
    for ($i=0; $i<7;  $i++){
        $tr->append($h->th($wdays_jp[$i])->attr('class','wday-td '.$wdays_en[$i]));
    } 
    $tbl->append($tr);
    $special = [];
    $n_row = 0;
    foreach ($acyear->getMonthDays($month) as $week ){
        $tr = $h->tr(); $n_row++; 
        foreach($week as $w=>$day){
            $td = $h->td();
            $class = 'mday-td';
            if ($day){
                $date = (new DateTime())->setDate($year, $month, $day);
                $td->append($date->format('d'));
                $class .= ' ' . $wdays_en[$w];
                $dt = $date->format('Y-m-d');
                if (key_exists($dt, $public)){
                    $dh = $public[$dt][0];
                    if (isset($ja[$dh])){
                       $special[$day][] = ['name'=>$ja[$dh], 'class'=>'holiday'];
                       $class .= ' holiday';
                    }
                }
                if (isset($local[$month][$day])){
                    $info = getInfo($local[$month][$day]);
                    foreach ($info as $item){
                        $special[$day][] = $item;
                        $class .= ' ' . $item['class'];
                    }
                }
                $td->attr('class', $class);
            }
            $tr->append($td);
        } 
        $tbl->append($tr);
    }
    $rs->append($tbl);
    foreach ($special as $day=>$items){
        foreach($items as $item){
            $name = $day . ' : ' . $item['name'];
            $span = $h->span($name)->attr('class', $item['class'].'_name');
            $rs->append($span);
            $rs->append($h->br());
        }     
    }
    return $rs;
};

function _class($dt) {
    return isset($dt['class']) ? $dt['class'] : 'calendar';
};

function getInfo($data){
    $rs = [];
    if (_class($data)=='list'){
        foreach ($data['items'] as $_=>$item){
            $rs[] = [
                'name'=>$item['name'], 'class' => _class($item)
            ];
        }
    }else{
        $rs[] = [
            'name'=>$data['name'], 'class' => _class($data)
        ];
    }
    return $rs;
}

/***
 * main program
 */
$jpy = $acyear->getJapneseYear(); 
$prev_y = $year - 1;
$next_y = $year + 1;
$h = new HtmlHelper();
$body = $h->div()->attr("align","center");
$title = $h->div( $jpy. "年度カレンダー")->attr('class',"year-title");
$body->append($title);
$nav1 = $h->a('<<前年度')->attr('href',"index.php?y=$prev_y");
$nav2 = $h->a('<<今年度>>')->attr('href',"index.php");
$nav3 = $h->a('来年度>>')->attr('href',"index.php?y=$next_y");
$body->append($children=[$nav1,'｜',$nav2,'｜',$nav3]);
$tbl = $h->table()->attr('class', 'days-table');
$tr = $h->tr();
foreach (range(1, 12) as $i){
    $month = 1+ (2 + $i) % 12;
    $tbmonth = $printMonth($month);
    $tr->append($tbmonth);
    if ($i % 6 ==0){
        $tbl->append($tr);
        $tr = $h->tr();
    }
}
$tbl->append($tr);
$body->append($tbl);
echo $body;
