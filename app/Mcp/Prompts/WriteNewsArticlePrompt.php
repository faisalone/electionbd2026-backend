<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class WriteNewsArticlePrompt extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = 'Generates a complete workflow prompt for writing a Bangla news article about Bangladesh elections from research to publication.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'topic',
                description: 'The main topic for the news article (e.g., "বাংলাদেশ নির্বাচন ২০২৬", "রাজনৈতিক দলের প্রস্তুতি")',
                required: true
            ),
            new Argument(
                name: 'category',
                description: 'The category for the article: "রাজনীতি", "নির্বাচন ২০২৬", or "নির্বাচন"',
                required: false
            ),
        ];
    }

    /**
     * Handle the prompt request.
     *
     * @return array<int, \Laravel\Mcp\Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'category' => 'nullable|string|in:রাজনীতি,নির্বাচন ২০২৬,নির্বাচন',
        ]);

        $topic = $validated['topic'];
        $category = $validated['category'] ?? 'নির্বাচন';

        $systemMessage = <<<SYSTEM
You are an AI assistant helping to generate Bangladesh election news articles in Bengali.

Your workflow consists of three steps:
1. **Fetch Sources**: Search for relevant news sources about the given topic
2. **Generate Article**: Create a well-written Bangla news article from those sources
3. **Publish**: Save the article to the database with duplicate detection

Follow these guidelines:
- Write ONLY in Bengali (বাংলা)
- Maintain journalistic integrity and accuracy
- Use neutral and professional tone
- Avoid bias or sensationalism
- Include verifiable facts from credible sources
- Check for duplicates before publishing
SYSTEM;

        $userMessage = <<<USER
I need to create a news article about: **{$topic}**

Category: {$category}

Please execute the following workflow:

### Step 1: Fetch News Sources
Use the FetchSourcesTool to search for relevant sources. Try these search queries:
- "{$topic}" (in Bengali)
- Translate the topic to English and search
- Related keywords: "বাংলাদেশ নির্বাচন", "Bangladesh election", "রাজনীতি"

The tool returns full-text excerpts (`content` and `excerpt`) from the approved Bangladeshi news sites—rely on that content for facts.

### Step 2: Generate Article
Once you have sources, use the GenerateArticleTool to create a Bangla article with:
- **Title** (শিরোনাম): Catchy and informative (max 100 characters)
- **Summary** (সারাংশ): Brief overview (150-200 characters)
- **Content** (বিষয়বস্তু): Full article (500-800 words)

### Step 3: Publish Article
Use the PublishArticleTool to save the article to the database.
- It will automatically check for duplicates
- If duplicate found, skip and report
- If new, publish and return the article ID

After completion, provide a summary of:
- Number of sources found
- Whether the article was generated successfully
- Whether it was published or skipped (duplicate)
- Article UID if published
USER;

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
