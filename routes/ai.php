<?php

use App\Mcp\Servers\NewsReporterServer;
use Laravel\Mcp\Facades\Mcp;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/*
|--------------------------------------------------------------------------
| AI Routes - Laravel MCP Server Registration
|--------------------------------------------------------------------------
|
| Here is where you can register MCP servers for your application.
| These servers expose tools, prompts, and resources to AI clients.
|
*/

// News Reporter MCP Server
// This server provides automated Bangla news generation for Bangladesh elections
Mcp::web('/mcp/news-reporter', NewsReporterServer::class)
    ->middleware(['throttle:60,1']) // Rate limit: 60 requests per minute
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Optional: Add authentication if needed
// Mcp::web('/mcp/news-reporter', NewsReporterServer::class)
//     ->middleware(['auth:sanctum', 'throttle:60,1']);

