<?php
/**
 * PHP version 5
 *
 * Copyright © Raimo Tuisku
 *
 * Made for a competition at https://github.com/solinor/PHPUG-Finland-Champion for fun. :-)
 *
 */
 
/**
 * Restaurant class stores the information of one restaurant and does intelligent string parsing for the opening times.
 * Can be instantiated like this: new Restaurant(1, 'X-Burger', 7.5)
 */
class Restaurant {
    private $DAY_NAMES = array('Ma', 'Ti', 'Ke', 'To', 'Pe', 'La', 'Su');
 
    function __construct($id, $name, $weekTotalHours=0) {
        $this->id = $id;
        $this->name = $name;
        $this->weekTotalHours = $weekTotalHours;
    }
 
    public function __toString() {
        return "$this->id. $this->name, open $this->weekTotalHours per week";
    }
 
    /**
     * Provides a way to input the opening times in a string, like setWeeklyTotalHoursFromString('Ma-Ke 10:00-12:00')
     */
    public function setWeeklyTotalHoursFromString($openingTimes) {
        $this->weekTotalHours = 0;
        $dayIntervalTimespans = explode(', ', $openingTimes);
        foreach($dayIntervalTimespans as $dayIntervalTimespan) {
            list($dayInterval, $timespans) = explode(' ', $dayIntervalTimespan, 2);
            list($start, $end) = explode('-', $dayInterval);
            if (!in_array($end, $this->DAY_NAMES)) {
                $end = $start;
            }
            $totalDays = (array_search($end, $this->DAY_NAMES) - array_search($start, $this->DAY_NAMES) + 1);
            $dayLengthInHours = 0;
            foreach (explode(' ja ', $timespans) as $timespan) {
                $dayLengthInHours += self::timespanLength($timespan);
            }
            $this->weekTotalHours += $dayLengthInHours * $totalDays;
            // Uncomment to see the logic working:
            // print_r(array('open' => $dayIntervalTimespan, 'days' => $totalDays, 'length' => $dayLengthInHours));
        }
    }
 
    static function timeOfDayInHours($timeStr) {
        list($hours,$minutes) = explode(':', $timeStr);
        return $hours + $minutes/60;
    }
 
    static function timespanLength($timespanStr) {
        list($start, $end) = explode('-', $timespanStr);
        $difference = self::timeOfDayInHours($end) - self::timeOfDayInHours($start);
        if ($difference < 0) {
            // the timespan continues to the next day!
            $difference += 24;
        }
        return $difference;
    }
 
    /* No Comparable interface even in PHP5 but this is as neat too */
    static function max($a, $b) { return ($a->weekTotalHours < $b->weekTotalHours) ? $b : $a; }
    static function min($a, $b) { return ($a->weekTotalHours > $b->weekTotalHours) ? $b : $a; }
}
 
/**
 * A class for error handling if the file opening fails.
 */
class InvalidCSVFile extends Exception {
    function __construct() {
        parent::__construct('Could not open the specified CSV file.');
    }
}
 
/**
 * RestaurantParser takes a CSV filename and computes the best and worst
 * restaurants by holding only 3 restaurants in a memory at a time.
 * Can be instantiated like this: new RestaurantParser('ravintolat.csv')
 */
class RestaurantParser {
    private $file;
 
    function __construct($filename, $delimiter=";") {
        $this->file = @fopen($filename, "r");
        if ($this->file === FALSE) {
            throw new InvalidCSVFile();
        }
        $this->delimiter = $delimiter;
        $this->computeBestWorst();
    }
    function computeBestWorst() {
        $this->best = new Restaurant(-1, 'noname', 0);
        $this->worst = new Restaurant(-1, 'noname', PHP_INT_MAX);
 
        while (($row = fgetcsv($this->file, 1000, $this->delimiter)) !== FALSE) {
            $restaurant = new Restaurant($row[0], $row[1]);
            $restaurant->setWeeklyTotalHoursFromString($row[4]);
            $this->best = Restaurant::max($this->best, $restaurant);
            $this->worst = Restaurant::min($this->worst, $restaurant);
        }
    }
    function getBest() {
        return $this->best;
    }
    function getWorst() {
        return $this->worst;
    }
 
    function __destruct() {
        fclose($this->file);
    }
}
 
try {
    if (count($argv) !== 2) {
        echo "Usage: $argv[0] [filename]\n";
    } else {
        $parser = new RestaurantParser($argv[1]);
        foreach (array($parser->getBest(), $parser->getWorst()) as $restaurant) {
            echo "$restaurant\n";
        }
    }
} catch (InvalidCSVFile $e) {
    echo "Sorry, " . $e->getMessage() . "\n";
}
?>