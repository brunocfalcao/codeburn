<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Process;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard');
    }

    public function data(): JsonResponse
    {
        $result = Process::path('/tmp')
            ->env(['HOME' => '/home/waygou'])
            ->timeout(30)
            ->run('sudo -u waygou /usr/bin/codeburn export -f json');

        $data = [];

        if ($result->successful()) {
            preg_match('/to:\s*(.+)$/m', $result->output(), $matches);

            if (! empty($matches[1])) {
                $filePath = trim($matches[1]);

                if (file_exists($filePath)) {
                    $data = json_decode(file_get_contents($filePath), true) ?? [];
                    @unlink($filePath);
                }
            }
        }

        return response()->json($data);
    }
}
