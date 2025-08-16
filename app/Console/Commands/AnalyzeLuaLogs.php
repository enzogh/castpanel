<?php

namespace App\Console\Commands;

use App\Services\Servers\LuaConsoleHookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AnalyzeLuaLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:analyze-logs 
                            {--file= : Path to specific log file to analyze}
                            {--server= : Analyze logs for specific server ID}
                            {--output= : Output format (text, json, csv)}
                            {--errors-only : Show only error lines}
                            {--with-stack : Include stack traces in output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze Lua log files for errors and generate detailed reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📄 Starting Lua Log Analysis...');
        
        try {
            $hookService = app(LuaConsoleHookService::class);
            
            // Configurer le serveur ciblé si spécifié
            if ($this->option('server')) {
                $serverId = (int) $this->option('server');
                $hookService->setTargetServerId($serverId);
                $this->info("🎯 Analyzing logs for server ID: {$serverId}");
            }
            
            // Activer le mode debug pour l'analyse
            $hookService->setDebugMode(true);
            
            // Analyser un fichier spécifique si fourni
            if ($this->option('file')) {
                $this->analyzeSpecificLogFile($this->option('file'));
                return 0;
            }
            
            // Analyser les logs des serveurs
            $this->analyzeServerLogs($hookService);
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to analyze logs: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Analyse un fichier de log spécifique
     */
    private function analyzeSpecificLogFile(string $filePath): void
    {
        $this->info("📁 Analyzing log file: {$filePath}");
        
        if (!File::exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return;
        }
        
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $totalLines = count($lines);
        
        $this->info("📊 Total lines in file: {$totalLines}");
        
        // Analyser le contenu avec le service
        $hookService = app(LuaConsoleHookService::class);
        $hookService->setDebugMode(true);
        
        $errors = $this->parseLogContent($lines);
        
        if (empty($errors)) {
            $this->info("✅ No Lua errors found in the log file");
            return;
        }
        
        $this->displayAnalysisResults($errors);
    }
    
    /**
     * Analyse les logs des serveurs
     */
    private function analyzeServerLogs(LuaConsoleHookService $hookService): void
    {
        $this->info('🔄 Starting server log analysis...');
        
        // Charger les serveurs et analyser leurs logs
        $hookService->startHooking();
    }
    
    /**
     * Parse le contenu d'un fichier de log
     */
    private function parseLogContent(array $lines): array
    {
        $errors = [];
        $totalLines = count($lines);
        
        for ($lineNumber = 0; $lineNumber < $totalLines; $lineNumber++) {
            $line = trim($lines[$lineNumber]);
            
            if (empty($line)) {
                continue;
            }
            
            // Détecter les erreurs Lua
            if ($this->isLuaError($line)) {
                $stackTrace = $this->captureStackTrace($lines, $lineNumber, $totalLines);
                $context = $this->captureErrorContext($lines, $lineNumber, $totalLines);
                $timestamp = $this->extractTimestampFromLogLine($line);
                
                $errors[] = [
                    'line' => $lineNumber + 1,
                    'content' => $line,
                    'type' => $this->classifyLuaError($line),
                    'timestamp' => $timestamp,
                    'stack_trace' => $stackTrace,
                    'context' => $context,
                    'raw_line' => $lines[$lineNumber]
                ];
            }
        }
        
        return $errors;
    }
    
    /**
     * Affiche les résultats de l'analyse
     */
    private function displayAnalysisResults(array $errors): void
    {
        $this->info("\n📊 Analysis Results:");
        $this->info("==================");
        
        foreach ($errors as $error) {
            $this->line("\n🚨 Error at line {$error['line']}:");
            $this->line("   Type: {$error['type']}");
            $this->line("   Content: {$error['content']}");
            
            if ($error['timestamp']) {
                $this->line("   Timestamp: {$error['timestamp']}");
            }
            
            if ($this->option('with-stack') && $error['stack_trace']) {
                $this->line("   Stack Trace:");
                $this->line($error['stack_trace']);
            }
            
            if ($this->option('with-stack') && $error['context']) {
                $this->line("   Context:");
                $this->line($error['context']);
            }
        }
        
        $this->info("\n📈 Summary: Found " . count($errors) . " Lua errors");
    }
    
    /**
     * Détecte si une ligne contient une erreur Lua
     */
    private function isLuaError(string $line): bool
    {
        $errorPatterns = [
            '/\[ERROR\]/i',
            '/lua error/i',
            '/attempt to call/i',
            '/attempt to index/i',
            '/bad argument/i',
            '/stack overflow/i',
            '/memory error/i',
            '/syntax error/i',
            '/runtime error/i',
            '/failed to load/i',
            '/could not load/i',
            '/error loading/i',
            '/addon.*not found/i',
            '/script.*failed/i',
            '/function.*error/i',
            '/nil value/i',
            '/invalid.*argument/i',
            '/out of memory/i',
            '/segmentation fault/i',
            '/access violation/i'
        ];

        foreach ($errorPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Classifie le type d'erreur Lua
     */
    private function classifyLuaError(string $line): string
    {
        if (preg_match('/\[ERROR\]/i', $line)) {
            return 'console_error';
        }
        
        if (preg_match('/attempt to call/i', $line)) {
            return 'function_call_error';
        }
        
        if (preg_match('/attempt to index/i', $line)) {
            return 'index_error';
        }
        
        if (preg_match('/bad argument/i', $line)) {
            return 'argument_error';
        }
        
        if (preg_match('/stack overflow/i', $line)) {
            return 'stack_overflow';
        }
        
        if (preg_match('/memory error/i', $line)) {
            return 'memory_error';
        }
        
        if (preg_match('/syntax error/i', $line)) {
            return 'syntax_error';
        }
        
        if (preg_match('/runtime error/i', $line)) {
            return 'runtime_error';
        }

        return 'unknown_error';
    }
    
    /**
     * Capture la stack trace après une erreur
     */
    private function captureStackTrace(array $lines, int $errorLineIndex, int $totalLines): string
    {
        $stackTrace = [];
        $maxLines = 20;
        
        for ($i = $errorLineIndex + 1; $i < min($errorLineIndex + $maxLines, $totalLines); $i++) {
            $line = trim($lines[$i]);
            
            if (empty($line)) {
                continue;
            }
            
            if (preg_match('/^[a-zA-Z]/', $line) && !preg_match('/^\s*at\s+/', $line)) {
                if (!preg_match('/error|exception|stack|trace/i', $line)) {
                    break;
                }
            }
            
            $stackTrace[] = $line;
        }
        
        return implode("\n", $stackTrace);
    }
    
    /**
     * Capture le contexte autour de l'erreur
     */
    private function captureErrorContext(array $lines, int $errorLineIndex, int $totalLines): string
    {
        $context = [];
        $contextLines = 5;
        
        $start = max(0, $errorLineIndex - $contextLines);
        for ($i = $start; $i < $errorLineIndex; $i++) {
            $line = trim($lines[$i]);
            if (!empty($line)) {
                $context[] = "  {$line}";
            }
        }
        
        $context[] = "→ " . trim($lines[$errorLineIndex]);
        
        for ($i = $errorLineIndex + 1; $i < min($errorLineIndex + $contextLines + 1, $totalLines); $i++) {
            $line = trim($lines[$i]);
            if (!empty($line)) {
                $context[] = "  {$line}";
            }
        }
        
        return implode("\n", $context);
    }
    
    /**
     * Extrait un timestamp depuis une ligne de log
     */
    private function extractTimestampFromLogLine(string $line): ?string
    {
        $timestampPatterns = [
            '/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/',
            '/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/',
            '/\[(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\]/',
            '/\[(\d{2}:\d{2}:\d{2})\]/',
            '/(\d{2}:\d{2}:\d{2})/'
        ];
        
        foreach ($timestampPatterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $timestamp = $matches[1];
                
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timestamp)) {
                    $timestamp = date('Y-m-d') . ' ' . $timestamp;
                } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $timestamp)) {
                    $timestamp = \DateTime::createFromFormat('d/m/Y H:i:s', $timestamp);
                    if ($timestamp) {
                        $timestamp = $timestamp->format('Y-m-d H:i:s');
                    }
                }
                
                return $timestamp;
            }
        }
        
        return null;
    }
}
