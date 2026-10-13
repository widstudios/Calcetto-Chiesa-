<?php

namespace App\Imports;

use RuntimeException;

/**
 * Riga del foglio non valida: il messaggio spiega cosa correggere.
 */
class RowError extends RuntimeException {}
