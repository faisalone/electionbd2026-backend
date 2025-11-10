<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\FetchSourcesTool;
use App\Mcp\Tools\GenerateArticleTool;
use App\Mcp\Tools\PublishArticleTool;
use App\Mcp\Prompts\WriteNewsArticlePrompt;
use Laravel\Mcp\Server;

class NewsReporterServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Bangladesh Election News Reporter';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'This server generates automated Bangla news articles about Bangladesh elections. It fetches sources, generates AI content, and publishes articles with duplicate detection.';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        FetchSourcesTool::class,
        GenerateArticleTool::class,
        PublishArticleTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        WriteNewsArticlePrompt::class,
    ];
}
