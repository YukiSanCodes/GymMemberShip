<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('gym:expire-memberships')->dailyAt('00:10');
