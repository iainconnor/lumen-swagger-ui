<?php

namespace IainConnor\LumenSwaggerUi;

class VendorNotFoundException extends \Exception
{
    protected $message = "Vendor directory not found.";
}
