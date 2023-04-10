<?php
namespace library;
use Yasumi\Yasumi;

class AcademicCalendar
{
	private $year;
    private $start_month = 4; // Apr. to Mar.
    public function __construct($year, $start=4){
        $this->year = $year;
        $this->start_month = $start; 
    }

    public function getJapneseYear()
    {
        $date = (new DateTime())->setDate($this->year, 4, 1);
        return $date->format('JK');
    }
    public function getMonthDays($month){
        $year = $month < $this->start_month ? $this->year+1 :$this->year;         
        $last_day = (int) date('t', mktime(0,0,0, $month, 1, $year));
        $week_of_firstday =(int)  date('w', mktime(0,0,0, $month, 1, $year)); 
        $week_of_lastday  = (int) date('w', mktime(0,0,0, $month, $last_day, $year));
        $aryCalendar = [];
        $j = 0;
        for($d = 0; $d < $week_of_firstday; $d++){
            $aryCalendar[$j][] = null;
        }
        for ($d = 1; $d <= $last_day; $d++){
            $aryCalendar[$j][] = $d; 
            if( ($d + $week_of_firstday) %7 == 0){
                $j++;
            }            
        }
        for($d = $week_of_lastday; $d < 7 ; $d++){
            $aryCalendar[$j][] = null;
        }
        return $aryCalendar;
    }

    public function getPublicHolidays(){
        $holidates = [];
        $holidays = [];
        $i = 0;
        for ($y= 0; $y < 2; $y++){
            $year = $this->year + $y;
		    $dh = Yasumi::create('Japan', $year);
            foreach ($dh->getHolidayDates() as $date ) {
                $holidates[] = $date;
            }
            foreach ($dh->getHolidayNames() as $name ) {
                preg_match('/^(substituteHoliday):(.+)/',$name, $matches);
                $d = $holidates[$i++];
                $holidays[$d] = [$name, null];     
                if ($matches) {
                    $holidays[$d] = [$matches[1], $matches[2] ];
                }            
            }
        }
		return $holidays;
	}
}
?>
