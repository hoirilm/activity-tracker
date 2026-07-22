<?php

class Carbon {
    public $ts;
    public function __construct($ts) { $this->ts = $ts; }
    public function getTimestamp() { return $this->ts; }
    public function diffInSeconds($other) { return $other->ts - $this->ts; }
}

class Activity {
    public $start_time;
    public $end_time;
    public function __construct($start, $end) {
        $this->start_time = new Carbon($start);
        $this->end_time = new Carbon($end);
    }
}

$activities = collect([
    new Activity(0, 10),
    new Activity(5, 15),
    new Activity(20, 30)
]);

function sumSeconds($activities) {
    if ($activities->isEmpty()) return 0;
    
    $intervals = $activities->map(function ($activity) {
        return [
            'start' => $activity->start_time->getTimestamp(),
            'end' => $activity->end_time->getTimestamp(),
        ];
    })->sortBy('start')->values();
    
    $merged = [];
    $currentStart = $intervals[0]['start'];
    $currentEnd = $intervals[0]['end'];
    
    for ($i = 1; $i < $intervals->count(); $i++) {
        $start = $intervals[$i]['start'];
        $end = $intervals[$i]['end'];
        
        if ($start <= $currentEnd) {
            $currentEnd = max($currentEnd, $end);
        } else {
            $merged[] = ['start' => $currentStart, 'end' => $currentEnd];
            $currentStart = $start;
            $currentEnd = $end;
        }
    }
    $merged[] = ['start' => $currentStart, 'end' => $currentEnd];
    
    $totalSeconds = 0;
    foreach ($merged as $interval) {
        $totalSeconds += ($interval['end'] - $interval['start']);
    }
    
    return $totalSeconds;
}

echo "Total: " . sumSeconds($activities) . "\n";
