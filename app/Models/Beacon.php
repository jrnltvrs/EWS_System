<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;      // ADD THIS
use Spatie\Activitylog\LogOptions;                // ADD THIS
use App\Jobs\SendBeaconStatusTelegramNotification;
use App\Services\SysLinkNotifier;

class Beacon extends Model
{
    use HasFactory;
    use LogsActivity;                              // ADD THIS

    protected $fillable = [
        'name',
        'location_id',
        'oid',
        'beacon_id',
        'group',
        'latitude',
        'longitude',
        'status',
        'last_seen_at',
        'is_door_open',
        'last_opened_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_seen_at' => 'datetime',
        'is_door_open' => 'boolean',
        'last_opened_at' => 'datetime',
    ];

    // ADD THIS METHOD - Required for auditing configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'location_id',
                'oid',
                'beacon_id',
                'group',
                'latitude',
                'longitude',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Beacon');
    }

    // Your existing methods (keep these as-is)
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getLocationNameAttribute(): string
    {
        return $this->location?->location_name ?? '—';
    }

    public static function getCounts(): array
    {
        return [
            'online' => self::where('status', true)->count(),
            'offline' => self::where('status', false)->count(),
        ];
    }


    protected static function booted()
    {
        static::updated(function (Beacon $beacon) {
            // Only log if the 'status' field was actually changed
            if ($beacon->wasChanged('status')) {
                self::logStatusChange($beacon);

                SendBeaconStatusTelegramNotification::dispatch(
                    $beacon->name,
                    $beacon->location?->location_name ?? '—',
                    $beacon->group,
                    (bool) $beacon->status,
                );
            }

            if ($beacon->wasChanged(['status', 'is_door_open'])) {
                SysLinkNotifier::pushAfterResponse();
            }
        });
    }

    protected static function logStatusChange(Beacon $beacon)
    {
        $newStatus = $beacon->status ? 'online' : 'offline';
        $eventTime = now();

        // Find the previous log to calculate duration
        $previousLog = BeaconStatusLog::where('beacon_identifier', $beacon->beacon_id)
            ->orderBy('event_time', 'desc')
            ->first();

        $duration = $previousLog
            ? $previousLog->event_time->diffInSeconds($eventTime)
            : null;

        BeaconStatusLog::create([
            'beacon_id'          => $beacon->id,           // integer foreign key
            'beacon_identifier'  => $beacon->beacon_id,    // the string ID (e.g. "16")
            'beacon_name'        => $beacon->name,
            'status'             => $newStatus,
            'event_time'         => $eventTime,
            'duration_seconds'   => $duration,
        ]);
    }
}