<?php

namespace App\Http\Controllers;

use App\Models\Deadline;
use App\Models\ScholarshipTracker;
use Illuminate\Http\Response;

/**
 * Streams the signed-in student's relevant deadlines as an .ics calendar
 * they can import into Google Calendar / Apple Calendar. Hand-rolled
 * VCALENDAR — no package needed for a handful of all-day VEVENTs.
 */
class DeadlineIcsController extends Controller
{
    public function __invoke(): Response
    {
        $deadlines = Deadline::relevantTo(auth()->user());

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//UniHup//Admission Deadlines//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:UniHup admission deadlines',
        ];

        foreach ($deadlines as $deadline) {
            $start = $deadline->due_at->copy()->startOfDay();
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:deadline-'.$deadline->id.'@unihup';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$start->copy()->addDay()->format('Ymd');
            $lines[] = 'SUMMARY:'.$this->escape($deadline->title.' — '.$deadline->scopeName());
            $lines[] = 'DESCRIPTION:'.$this->escape(trim(
                ($deadline->description ? $deadline->description."\n" : '').
                $deadline->categoryLabel().
                ($deadline->url ? "\n".$deadline->url : '')
            ));
            $lines[] = 'END:VEVENT';
        }

        $scholarships = ScholarshipTracker::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('deadline_at')
            ->get();

        foreach ($scholarships as $s) {
            $start = $s->deadline_at->copy()->startOfDay();
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:scholarship-'.$s->id.'@unihup';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$start->copy()->addDay()->format('Ymd');
            $lines[] = 'SUMMARY:'.$this->escape($s->label.' — scholarship deadline');
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="unihup-deadlines.ics"',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', "\n", ',', ';'],
            ['\\\\', '\\n', '\\,', '\\;'],
            $value,
        );
    }
}
