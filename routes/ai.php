<?php

use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\AIServer;

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);
Mcp::web('/mcp/servers', AIServer::class);

