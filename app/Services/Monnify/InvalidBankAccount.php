<?php

declare(strict_types=1);

namespace App\Services\Monnify;

/** The bank says this account doesn't exist, as opposed to Monnify being unavailable. */
class InvalidBankAccount extends MonnifyException {}
