<?php

declare (strict_types=1);

namespace GianTiaga\SpiralOpenApi\Model;

enum OperationSecurity
{
    case Public;
    case Bearer;
}
