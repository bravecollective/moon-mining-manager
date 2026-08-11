<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

class InvalidConfigurationException extends RuntimeException
{
    /**
     * @var string[]
     */
    private $missingVariables;

    /**
     * @param string[] $missingVariables
     */
    public function __construct(array $missingVariables)
    {
        $this->missingVariables = $missingVariables;

        parent::__construct(
            'The application is incorrectly configured. Missing required environment variables: '
            . implode(', ', $missingVariables) . '.'
        );
    }

    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'missing' => $this->missingVariables,
            ], 500);
        }

        return response()->view('errors.configuration', [
            'missingVariables' => $this->missingVariables,
        ], 500);
    }
}
