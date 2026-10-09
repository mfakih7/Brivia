<?php

// Child process for MySqlConcurrencyTest. Usage: php confirm-appointment.php <appointmentId> <assigneeId> <Y-m-d H:i:s> <H:i>
// Runs only with APP_ENV=testing against the dedicated MySQL test database supplied via environment variables.

use App\Exceptions\SchedulingConflict;
use App\Models\Appointment;
use App\Models\User;
use App\Support\AppointmentScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    fwrite(STDERR, "refused: not the testing MySQL database\n");
    exit(2);
}

[, $appointmentId, $assigneeId, $startDay, $time] = $argv;
$appointment = Appointment::findOrFail((int) $appointmentId);
$actor = User::findOrFail((int) $assigneeId);
$start = CarbonImmutable::parse(substr($startDay, 0, 10).' '.$time.':00', 'UTC');

try {
    app(AppointmentScheduler::class)->confirm($actor, $appointment, $appointment->lock_version, (int) $assigneeId, $start, 60, null);
    echo "confirmed\n";
} catch (SchedulingConflict $e) {
    echo 'conflict: '.$e->getMessage()."\n";
}
