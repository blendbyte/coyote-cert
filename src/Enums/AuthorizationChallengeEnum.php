<?php

namespace CoyoteCert\Enums;

enum AuthorizationChallengeEnum: string
{
    case HTTP = 'http-01';
    case DNS = 'dns-01';
}
