<?php

function countComplexity($content) {
    $ccm = 1;
    $keywords = ['if', 'while', 'for', 'foreach', 'case', 'catch', '?', '&&', '||'];

    foreach ($keywords as $keyword) {
        if (in_array($keyword, ['&&', '||', '?'])) {
            $ccm += substr_count($content, $keyword);
        } else {
            preg_match_all("/\b$keyword\b/i", $content, $matches);
            $ccm += count($matches[0]);
        }
    }

    return $ccm;
}

function extractClassName($content) {
    if (preg_match('/\bclass\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches)) {
        return $matches[1];
    }

    return '';
}

function extractParentClass($content) {
    if (preg_match('/\bclass\s+[a-zA-Z_][a-zA-Z0-9_]*\s+extends\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches)) {
        return $matches[1];
    }

    return '';
}

function calculateDIT($className, $parents) {
    $depth = 0;
    $current = $className;

    while (!empty($parents[$current])) {
        $depth++;
        $current = $parents[$current];
    }

    return $depth;
}

function calculateCBO($content, $className, $allClasses) {
    $coupled = [];

    foreach ($allClasses as $candidate) {
        if ($candidate === $className || $candidate === '') {
            continue;
        }

        if (preg_match('/\b' . preg_quote($candidate, '/') . '\b/', $content)) {
            $coupled[$candidate] = true;
        }
    }

    if (preg_match_all('/\bnew\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches)) {
        foreach ($matches[1] as $candidate) {
            if ($candidate !== $className) {
                $coupled[$candidate] = true;
            }
        }
    }

    return count($coupled);
}

function calculateRFC($content, $wmc) {
    preg_match_all('/(?:->|::)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $methodCalls);
    $distinctCalls = array_unique($methodCalls[1]);

    return $wmc + count($distinctCalls);
}

function calculateLCOM($content) {
    preg_match_all('/function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{(.*?)\n\s*\}/is', $content, $methodBodies);

    $methodsUsingFields = 0;
    foreach ($methodBodies[1] as $body) {
        if (preg_match('/\$this->/', $body)) {
            $methodsUsingFields++;
        }
    }

    $methodCount = count($methodBodies[1]);
    if ($methodCount === 0) {
        return 0;
    }

    return $methodsUsingFields === $methodCount ? 0 : 1;
}

function calculateMetrics($dir, $allClasses, $parents, $children) {
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            
            // LOC (Lines of Code)
            $loc = substr_count($content, "\n") + 1;
            
            $ccm = countComplexity($content);

            // OO Complexity (Count of methods/functions, class definition)
            preg_match_all("/\bfunction\s+[a-zA-Z0-9_]+\s*\(/i", $content, $methods);
            $methodCount = count($methods[0]);
            $className = extractClassName($content);
            $wmc = $ccm;
            $dit = $className ? calculateDIT($className, $parents) : 0;
            $noc = $className && isset($children[$className]) ? count($children[$className]) : 0;
            $cbo = calculateCBO($content, $className, $allClasses);
            $rfc = calculateRFC($content, $wmc);
            $lcom = calculateLCOM($content);
            
            $results[] = [
                'File' => $file->getFilename(),
                'Path' => str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'Class' => $className,
                'LOC' => $loc,
                'CCM' => $ccm,
                'Methods_Count' => $methodCount,
                'Avg_CCM_Per_Method' => $methodCount > 0 ? round($ccm / $methodCount, 2) : $ccm,
                'WMC' => $wmc,
                'DIT' => $dit,
                'NOC' => $noc,
                'CBO' => $cbo,
                'RFC' => $rfc,
                'LCOM' => $lcom
            ];
        }
    }
    return $results;
}

$scanDirs = [__DIR__ . '/Controllers', __DIR__ . '/Models'];
$allClasses = [];
$parents = [];
$children = [];

foreach ($scanDirs as $scanDir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $className = extractClassName($content);
            if ($className !== '') {
                $allClasses[] = $className;
                $parentClass = extractParentClass($content);
                if ($parentClass !== '') {
                    $parents[$className] = $parentClass;
                    $children[$parentClass][] = $className;
                }
            }
        }
    }
}

$controllers = calculateMetrics(__DIR__ . '/Controllers', $allClasses, $parents, $children);
$models = calculateMetrics(__DIR__ . '/Models', $allClasses, $parents, $children);

$all = array_merge($controllers, $models);

$output = "File,Path,Class,LOC,CCM (Complexity),Total Methods,Avg CCM per Method,WMC = sum(method complexities),DIT = depth of inheritance,NOC = immediate child classes,CBO = coupled classes,RFC = WMC + distinct method calls,LCOM = 0 cohesive / 1 low cohesion\n";
foreach ($all as $row) {
    $output .= "{$row['File']},{$row['Path']},{$row['Class']},{$row['LOC']},{$row['CCM']},{$row['Methods_Count']},{$row['Avg_CCM_Per_Method']},{$row['WMC']},{$row['DIT']},{$row['NOC']},{$row['CBO']},{$row['RFC']},{$row['LCOM']}\n";
}

file_put_contents(__DIR__ . '/Metrics_Report.csv', $output);
echo "Metrics calculated successfully.\n";
