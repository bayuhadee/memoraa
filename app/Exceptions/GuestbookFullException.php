<?php

namespace App\Exceptions;

use RuntimeException;

/** Jumlah tamu di event sudah mencapai batas yang diatur owner. */
class GuestbookFullException extends RuntimeException {}
