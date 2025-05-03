<?php include "db_handler.php"; ?>
<!DOCTYPE html>
<html>
	<?php  
  ob_start(); // Start output buffering

		include "header.php"; 
		include "lecturer-navbar.php"; 
	?>
<head>
    <meta charset="UTF-8">
    <!-- Bootstrap CSS File  -->
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.7.2/css/bootstrap-slider.min.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.7.2/bootstrap-slider.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
    <title>Document Analysis System</title>
	<style>
.dropbtn {
  background-color: darkblue;
  color: white;
  padding: 16px;
  font-size: 16px;
  border: none;
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f1f1f1;
  min-width: 160px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

.dropdown-content a {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
}

.dropdown-content a:hover {background-color: #ddd;}

.dropdown:hover .dropdown-content {display: block;}

.dropdown:hover .dropbtn {background-color: grey;}

.ai-report, .plagiarism-report {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.ai-score, .plagiarism-score {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

.ai-score-high, .plagiarism-score-high {
    color: #d9534f;
}

.ai-score-medium, .plagiarism-score-medium {
    color: #f0ad4e;
}

.ai-score-low, .plagiarism-score-low {
    color: #5cb85c;
}

.feedback-section {
    margin-top: 15px;
    padding: 10px;
    background-color: #eef7fb;
    border-left: 4px solid #5bc0de;
}

.highlighted-text {
    background-color: #ffff99;
    padding: 2px;
    border-radius: 3px;
}

.tab-content {
    border: 1px solid #ddd;
    border-top: none;
    padding: 15px;
    background-color: #fff;
}

.nav-tabs {
    margin-bottom: 0;
}

.progress-bar-custom {
    background-color: #5bc0de;
}

.match-source {
    margin-bottom: 10px;
    padding: 10px;
    background-color: #f5f5f5;
    border-left: 3px solid #d9534f;
}

.highlight-legend {
    margin-top: 15px;
    padding: 5px;
    font-size: 12px;
}

.highlight-legend span {
    padding: 2px 5px;
    margin-right: 10px;
}

.ai-highlight {
    background-color: #d9edf7;
}

.plagiarism-highlight {
    background-color: #ffff99;
}
</style>
</head>
<body style="background-color:lightblue;">
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Document Analysis System</h2>
            <p>Select a text file to analyze for AI-generated content and plagiarism.</p>
            
            <!-- Analysis Type Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#file-selection" aria-controls="file-selection" role="tab" data-toggle="tab">File Selection</a></li>
                <li role="presentation"><a href="#ai-detection" aria-controls="ai-detection" role="tab" data-toggle="tab">AI Detection</a></li>
                <li role="presentation"><a href="#plagiarism" aria-controls="plagiarism" role="tab" data-toggle="tab">Plagiarism Checking</a></li>
                <li role="presentation"><a href="#combined" aria-controls="combined" role="tab" data-toggle="tab">Combined Analysis</a></li>
            </ul>
        </div>
    </div>
    
    <div class="tab-content">
        <!-- File Selection Tab -->
        <div role="tabpanel" class="tab-pane active" id="file-selection">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Available Files</h3>
                        </div>
                        <div class="panel-body">
                            <?php
                            $fileList = glob('*.txt');
                            if (empty($fileList)) {
                                echo '<p>No text files found.</p>';
                            } else {
                                echo '<ul class="list-group">';
                                foreach($fileList as $filename) {
                                    echo '<li class="list-group-item">' . $filename . '</li>';
                                }
                                echo '</ul>';
                            }
                            ?>
                            
                            <form method="post" class="form-inline">
                                <div class="form-group">
                                    <label for="fname">File name:</label>
                                    <input type="text" class="form-control" id="fname" name="fname" placeholder="Enter file name">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Open File</button>
                                <button type="submit" name="analyze_ai" class="btn btn-success">AI Analysis</button>
                                <button type="submit" name="analyze_plagiarism" class="btn btn-warning">Plagiarism Check</button>
                                <button type="submit" name="analyze_combined" class="btn btn-info">Combined Analysis</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php
                    // Display file content if requested
                    if(isset($_POST['submit']) || isset($_POST['analyze_ai']) || isset($_POST['analyze_plagiarism']) || isset($_POST['analyze_combined'])) {
                        if(empty($_POST['fname'])) {
                            echo '<div class="alert alert-danger">Please enter a file name</div>';
                        } else {
                            $filename = $_POST['fname'];
                            $file = @fopen($filename, "r");
                            
                            if($file == false) {
                                echo '<div class="alert alert-danger">Error: Could not open file "' . htmlspecialchars($filename) . '"</div>';
                            } else {
                                $fsize = filesize($filename);
                                $filetxt = fread($file, $fsize);
                                fclose($file);
                                
                                echo '<div class="panel panel-default">';
                                echo '<div class="panel-heading"><h3 class="panel-title">File Content: ' . htmlspecialchars($filename) . '</h3></div>';
                                echo '<div class="panel-body"><pre>' . htmlspecialchars($filetxt) . '</pre></div>';
                                echo '</div>';
                                
                                // Store file content in session for tab switching
                                $_SESSION['current_file'] = $filename;
                                $_SESSION['file_content'] = $filetxt;
                                
                                // Redirect to appropriate tab based on analysis request
                                if(isset($_POST['analyze_ai'])) {
                                    echo "<script>$(function() { $('a[href=\"#ai-detection\"]').tab('show'); });</script>";
                                } else if(isset($_POST['analyze_plagiarism'])) {
                                    echo "<script>$(function() { $('a[href=\"#plagiarism\"]').tab('show'); });</script>";
                                } else if(isset($_POST['analyze_combined'])) {
                                    echo "<script>$(function() { $('a[href=\"#combined\"]').tab('show'); });</script>";
                                }
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- AI Detection Tab -->
        <div role="tabpanel" class="tab-pane" id="ai-detection">
            <?php
            // Function to detect AI-generated content using advanced algorithms
            function detectAIContent($text) {
                // AI detection indicators (enhanced)
                $indicators = [
                    'repetitive patterns' => preg_match('/(.{20,})\1{1,}/is', $text) ? 1 : 0,
                    'formulaic structure' => preg_match('/(?:first(?:ly)?|initially|to begin with).*(?:second(?:ly)?|next|then).*(?:third(?:ly)?|additionally|moreover).*(?:finally|in conclusion|to summarize)/is', $text) ? 1 : 0,
                    'generic phrases' => preg_match_all('/(in conclusion|to summarize|as we can see|it is important to note that|as mentioned earlier|it goes without saying|needless to say|it is worth mentioning)/i', $text, $matches),
                    'lack of errors' => (stripos($text, 'typo') === false && stripos($text, 'error') === false && !preg_match('/[a-z][A-Z]/', $text)) ? 1 : 0,
                    'excessive formality' => preg_match_all('/\b(furthermore|moreover|nevertheless|subsequently|consequently|notwithstanding|aforementioned|heretofore)\b/i', $text, $matches),
                    'perfect citation format' => preg_match_all('/\([A-Za-z]+, \d{4}(, p\. \d+)?\)/', $text, $matches),
                ];
                
                // Analyze lexical diversity (AI often has lower diversity)
                $words = str_word_count(strtolower($text), 1);
                $unique_words = count(array_unique($words));
                $total_words = count($words);
                $lexical_diversity = $total_words > 0 ? $unique_words / $total_words : 0;
                $low_diversity = $lexical_diversity < 0.4 && $total_words > 100;
                
                // Count word frequency for unusual distribution patterns
                $word_freq = array_count_values($words);
                arsort($word_freq);
                $top_words = array_slice($word_freq, 0, 10, true);
                
                // Analyze sentence length variation
                $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                $sentence_lengths = array_map('strlen', $sentences);
                $avg_length = array_sum($sentence_lengths) / max(1, count($sentence_lengths));
                $length_variance = 0;
                foreach ($sentence_lengths as $length) {
                    $length_variance += pow($length - $avg_length, 2);
                }
                $length_variance /= max(1, count($sentence_lengths));
                $low_variance = $length_variance < 200; // Low variance indicates AI
                
                // Analyze transition uniformity (AI often uses uniform transitions)
                $transition_words = ['however', 'therefore', 'thus', 'hence', 'consequently', 'furthermore', 'moreover'];
                $transition_count = 0;
                foreach ($transition_words as $word) {
                    $transition_count += substr_count(strtolower($text), $word);
                }
                $transition_density = $total_words > 0 ? $transition_count / $total_words : 0;
                $high_transition_uniformity = $transition_density > 0.02;
                
                // Natural language quirks detection (AIs often lack these)
                $natural_quirks = [
                    'contractions' => preg_match_all("/\b(can't|won't|wouldn't|shouldn't|don't|doesn't|isn't|aren't|wasn't|weren't)\b/i", $text, $matches),
                    'idioms' => preg_match_all('/\b(out of the blue|piece of cake|break a leg|hit the road|under the weather)\b/i', $text, $matches),
                    'colloquialisms' => preg_match_all('/\b(gonna|wanna|gotta|sorta|kinda|y\'all|ain\'t)\b/i', $text, $matches),
                    'hedging' => preg_match_all('/\b(sort of|kind of|maybe|perhaps|possibly|seems like|appears to be)\b/i', $text, $matches),
                ];
                $natural_language_score = array_sum($natural_quirks);
                $lacks_natural_quirks = $natural_language_score < 2 && $total_words > 300;
                
                // Find perplexing sections (AI typically avoids these)
                $perplexing_patterns = preg_match_all('/(\?\?\?|\[citation needed\]|not sure about this|check this later)/i', $text, $matches);
                
                // Calculate AI probability score (0-100)
                $score_factors = [
                    $indicators['repetitive patterns'] ? 12 : 0,
                    $indicators['formulaic structure'] ? 10 : 0,
                    min(15, $indicators['generic phrases'] * 3),
                    $indicators['lack of errors'] ? 8 : 0,
                    min(12, $indicators['excessive formality'] * 2),
                    $indicators['perfect citation format'] > 3 ? 8 : 0,
                    $low_variance ? 15 : 0,
                    $low_diversity ? 10 : 0,
                    $high_transition_uniformity ? 8 : 0,
                    $lacks_natural_quirks ? 10 : 0,
                    $perplexing_patterns == 0 ? 5 : 0,
                ];
                
                $score = array_sum($score_factors);
                
                // Identify potentially AI-generated paragraphs
                $paragraphs = explode("\n\n", $text);
                $ai_paragraphs = [];
                
                foreach ($paragraphs as $index => $paragraph) {
                    if (strlen($paragraph) < 100) continue; // Skip very short paragraphs
                    
                    // Simplified per-paragraph analysis
                    $para_score = 0;
                    if (preg_match('/(.{20,})\1{1,}/is', $paragraph)) $para_score += 10;
                    if (preg_match_all('/(in conclusion|to summarize|as we can see|it is important to note that)/i', $paragraph, $matches)) $para_score += count($matches[0]) * 5;
                    if (preg_match_all('/\b(furthermore|moreover|nevertheless|subsequently)\b/i', $paragraph, $matches)) $para_score += count($matches[0]) * 3;
                    
                    // Check paragraph sentence variance
                    $para_sentences = preg_split('/(?<=[.!?])\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY);
                    if (count($para_sentences) >= 3) {
                        $para_lengths = array_map('strlen', $para_sentences);
                        $para_avg = array_sum($para_lengths) / count($para_lengths);
                        $para_variance = 0;
                        foreach ($para_lengths as $length) {
                            $para_variance += pow($length - $para_avg, 2);
                        }
                        $para_variance /= count($para_lengths);
                        if ($para_variance < 100) $para_score += 15;
                    }
                    
                    if ($para_score >= 25) {
                        $ai_paragraphs[] = $index;
                    }
                }
                
                // Generate detailed report
                $report = [
                    'score' => min(100, max(0, $score)),
                    'indicators' => $indicators,
                    'frequent_words' => $top_words,
                    'sentence_analysis' => [
                        'avg_length' => $avg_length,
                        'variance' => $length_variance,
                        'count' => count($sentences)
                    ],
                    'lexical_diversity' => [
                        'unique_words' => $unique_words,
                        'total_words' => $total_words,
                        'diversity_score' => $lexical_diversity
                    ],
                    'natural_language' => $natural_quirks,
                    'ai_paragraphs' => $ai_paragraphs,
                    'feedback' => generateAIFeedback($score, $indicators, $low_variance, $low_diversity, $natural_language_score)
                ];
                
                return $report;
            }
            
            // Function to generate feedback based on AI detection results
            function generateAIFeedback($score, $indicators, $low_variance, $low_diversity, $natural_language_score) {
                $feedback = [];
                
                if ($score < 30) {
                    $feedback[] = "The content appears to be mostly human-written with high confidence.";
                } elseif ($score < 60) {
                    $feedback[] = "The content shows some characteristics of AI-generated text, possibly edited by a human.";
                } else {
                    $feedback[] = "The content strongly resembles AI-generated text with high confidence.";
                }
                
                // Specific feedback based on indicators
                if ($indicators['repetitive patterns']) {
                    $feedback[] = "Repetitive patterns detected: Consider rewording sections that use similar phrasing multiple times.";
                }
                
                if ($indicators['formulaic structure']) {
                    $feedback[] = "Formulaic structure detected: The organization follows a predictable pattern common in AI writing. Consider restructuring to add more originality.";
                }
                
                if ($indicators['generic phrases'] > 2) {
                    $feedback[] = "Generic transitional phrases: Replace formulaic transitions with more original language to improve authenticity.";
                }
                
                if ($indicators['excessive formality'] > 3) {
                    $feedback[] = "Excessive formality: The text uses formal academic language that may appear unnatural. Consider using more varied and conversational language.";
                }
                
                if ($low_variance) {
                    $feedback[] = "Uniform sentence structure: Vary sentence length and structure more to create a more natural rhythm and flow.";
                }
                
                if ($low_diversity) {
                    $feedback[] = "Limited vocabulary diversity: The text uses a relatively limited range of words. Consider incorporating more varied vocabulary.";
                }
                
                if ($natural_language_score < 2) {
                    $feedback[] = "Lack of natural language elements: Consider adding contractions, idioms, or occasional colloquialisms to make the text feel more human-written.";
                }
                
                return $feedback;
            }
            
            // Process AI content detection
            if(isset($_POST['analyze_ai']) || isset($_POST['analyze_combined'])) {
                if(isset($_SESSION['file_content']) && !empty($_SESSION['file_content'])) {
                    $filename = $_SESSION['current_file'];
                    $filetxt = $_SESSION['file_content'];
                    
                    $ai_report = detectAIContent($filetxt);
                    $_SESSION['ai_report'] = $ai_report; // Store for combined analysis
                    
                    // Display score with appropriate color
                    $score_class = '';
                    if ($ai_report['score'] >= 70) {
                        $score_class = 'ai-score-high';
                    } elseif ($ai_report['score'] >= 30) {
                        $score_class = 'ai-score-medium';
                    } else {
                        $score_class = 'ai-score-low';
                    }
                    
                    echo '<div class="ai-report">';
                    echo '<h3>AI Content Analysis Report</h3>';
                    echo '<div class="ai-score ' . $score_class . '">AI Probability Score: ' . $ai_report['score'] . '%</div>';
                    
                    echo '<div class="progress">';
                    echo '<div class="progress-bar progress-bar-custom" role="progressbar" aria-valuenow="' . $ai_report['score'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $ai_report['score'] . '%;">';
                    echo $ai_report['score'] . '%';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<h4>Detailed Analysis</h4>';
                    echo '<div class="row">';
                    echo '<div class="col-md-6">';
                    echo '<ul>';
                    echo '<li>Repetitive patterns detected: ' . ($ai_report['indicators']['repetitive patterns'] ? 'Yes' : 'No') . '</li>';
                    echo '<li>Formulaic structure: ' . ($ai_report['indicators']['formulaic structure'] ? 'Yes' : 'No') . '</li>';
                    echo '<li>Generic phrases count: ' . $ai_report['indicators']['generic phrases'] . '</li>';
                    echo '<li>Excessive formality markers: ' . $ai_report['indicators']['excessive formality'] . '</li>';
                    echo '<li>Perfect citation format instances: ' . $ai_report['indicators']['perfect citation format'] . '</li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '<div class="col-md-6">';
                    echo '<ul>';
                    echo '<li>Average sentence length: ' . round($ai_report['sentence_analysis']['avg_length'], 2) . ' characters</li>';
                    echo '<li>Sentence length variance: ' . round($ai_report['sentence_analysis']['variance'], 2) . ' (Low variance may indicate AI)</li>';
                    echo '<li>Lexical diversity: ' . round($ai_report['lexical_diversity']['diversity_score'] * 100, 2) . '% (' . $ai_report['lexical_diversity']['unique_words'] . ' unique words out of ' . $ai_report['lexical_diversity']['total_words'] . ' total)</li>';
                    echo '<li>Natural language elements: ' . array_sum($ai_report['natural_language']) . ' instances</li>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="feedback-section">';
                    echo '<h4>Feedback & Recommendations</h4>';
                    echo '<ul>';
                    foreach ($ai_report['feedback'] as $item) {
                        echo '<li>' . $item . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    
                    // Highlighted text with AI-generated paragraphs
                    if (!empty($ai_report['ai_paragraphs'])) {
                        echo '<h4>Content Analysis Visualization</h4>';
                        echo '<p>Paragraphs highlighted in blue show strong AI-generation characteristics:</p>';
                        $paragraphs = explode("\n\n", $filetxt);
                        echo '<div style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd;">';
                        foreach ($paragraphs as $index => $paragraph) {
                            if (in_array($index, $ai_report['ai_paragraphs'])) {
                                echo '<p class="ai-highlight">' . htmlspecialchars($paragraph) . '</p>';
                            } else {
                                echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                            }
                        }
                        echo '</div>';
                    }
                    
                    echo '<h4>Most Frequent Words</h4>';
                    echo '<div class="row">';
                    echo '<div class="col-md-6">';
                    echo '<table class="table table-striped table-sm">';
                    echo '<thead><tr><th>Word</th><th>Frequency</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($ai_report['frequent_words'] as $word => $freq) {
                        echo '<tr><td>' . htmlspecialchars($word) . '</td><td>' . $freq . '</td></tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Download report option
                    echo '<form method="post">';
                    echo '<input type="hidden" name="report_filename" value="' . htmlspecialchars($filename) . '">';
                    echo '<input type="hidden" name="report_type" value="ai">';
                    echo '<input type="hidden" name="report_score" value="' . $ai_report['score'] . '">';
                    echo '<button type="submit" name="download_report" class="btn btn-info">Download AI Analysis Report</button>';
                    echo '</form>';
                    
                    echo '</div>'; // end .ai-report
                } else {
                    echo '<div class="alert alert-warning">Please select a file first.</div>';
                }
            }
            ?>
        </div>
        
        <!-- Plagiarism Tab -->
        <div role="tabpanel" class="tab-pane" id="plagiarism">
            <?php
            // Function to detect plagiarism using algorithms
            function detectPlagiarism($text, $databasePath = 'plagiarism_database') {
                // Create database directory if it doesn't exist
                if (!file_exists($databasePath)) {
                    mkdir($databasePath, 0777, true);
                }
                
                // Preprocess text
                $text = strtolower($text);
                $text = preg_replace('/[^\w\s]/', '', $text);
                
                // Generate n-grams from text (for fingerprinting)
                function generateNGrams($text, $n = 5) {
                    $words = explode(' ', $text);
                    $ngrams = [];
                    for ($i = 0; $i <= count($words) - $n; $i++) {
                        $ngrams[] = implode(' ', array_slice($words, $i, $n));
                    }
                    return $ngrams;
                }
                
                // Winnowing algorithm to select fingerprints
                function winnow($ngrams, $w = 10) {
                    $hashes = array_map('md5', $ngrams);
                    $fingerprints = [];
                    $positions = [];
                    
                    for ($i = 0; $i <= count($hashes) - $w; $i++) {
                        $window = array_slice($hashes, $i, $w);
                        $minHash = min($window);
                        $minPos = array_search($minHash, $window) + $i;
                        
                        if (!isset($positions[$minHash]) || $positions[$minHash] !== $minPos) {
                            $fingerprints[$minPos] = $minHash;
                            $positions[$minHash] = $minPos;
                        }
                    }
                    
                    return $fingerprints;
                }
                
                // Split text into paragraphs for analysis
                $paragraphs = explode("\n\n", $text);
                $textFingerprints = [];
                $paragraphFingerprints = [];
                
                foreach ($paragraphs as $index => $paragraph) {
                    if (strlen($paragraph) < 100) continue; // Skip very short paragraphs
                    
                    $ngrams = generateNGrams($paragraph);
                    $fingerprints = winnow($ngrams);
                    $paragraphFingerprints[$index] = $fingerprints;
                    $textFingerprints = array_merge($textFingerprints, $fingerprints);
                }
                
                // Mock database of documents (in a real system, would scan files or use a database)
                $sampleDocs = [
                    'sample1.txt' => "This is an example document that contains some content that might be plagiarized. The detection system should be able to identify similarities between documents and flag potential instances of plagiarism. Plagiarism is the practice of taking someone else's work or ideas and passing them off as one's own.",
                    'sample2.txt' => "Artificial intelligence is transforming many fields including education. AI detection systems can identify content that was generated by large language models rather than written by humans. These systems analyze patterns, structures, and other indicators to determine the likelihood that content was AI-generated.",
                    'sample3.txt' => "Academic integrity requires proper attribution of sources. When students submit work that includes ideas or text from other sources without proper citation, it constitutes plagiarism. Educational institutions have policies to address plagiarism and other forms of academic dishonesty."
                ];
                
                // Store sample documents in database directory (for demo purposes)
                foreach ($sampleDocs as $filename => $content) {
                    if (!file_exists("$databasePath/$filename")) {
                        file_put_contents("$databasePath/$filename", $content);
                    }
                }
                
                // Analyze document collection for matches
                $matches = [];
                $matchPercentages = [];
                $totalMatches = 0;
                $plagiarizedParagraphs = [];
                
                // Scan sample docs and database files
                $allDocs = array_merge($sampleDocs, []);
                $dbFiles = glob("$databasePath/*.txt");
                foreach ($dbFiles as $file) {
                    $baseName = basename($file);
                    if (!isset($allDocs[$baseName])) {
                        $allDocs[$baseName] = file_get_contents($file);
                    }
                }
                
                foreach ($allDocs as $filename => $docContent) {
                    // Preprocess comparison document
                    $docContent = strtolower($docContent);
                    $docContent = preg_replace('/[^\w\s]/', '', $docContent);
                    
                    // Generate fingerprints for comparison document
                    $docNgrams = generateNGrams($docContent);
                    $docFingerprints = winnow($docNgrams);
                    
                    // Find matching fingerprints
                    $docMatches = array_intersect($textFingerprints, $docFingerprints);
                    $matchCount = count($docMatches);
                    
                    if ($matchCount > 0) {
                      $matchPercentage = round(($matchCount / max(1, count($textFingerprints))) * 100, 2);
                      
                      if ($matchPercentage >= 5) { // Threshold for considering a match
                          $matches[$filename] = [
                              'count' => $matchCount,
                              'percentage' => $matchPercentage,
                              'content' => $docContent
                          ];
                          $totalMatches += $matchPercentage;
                          
                          // Find which paragraphs match
                          foreach ($paragraphFingerprints as $pIndex => $pFingerprints) {
                              $pMatches = array_intersect($pFingerprints, $docFingerprints);
                              if (count($pMatches) > 0) {
                                  $pMatchPercentage = round((count($pMatches) / max(1, count($pFingerprints))) * 100, 1);
                                  if ($pMatchPercentage >= 20) { // Higher threshold for paragraph matching
                                      if (!isset($plagiarizedParagraphs[$pIndex])) {
                                          $plagiarizedParagraphs[$pIndex] = [];
                                      }
                                      $plagiarizedParagraphs[$pIndex][] = [
                                          'source' => $filename,
                                          'percentage' => $pMatchPercentage
                                      ];
                                  }
                              }
                          }
                      }
                  }
              }
              
              // Calculate overall plagiarism score (0-100)
              $plagiarismScore = min(100, $totalMatches);
              
              // Generate feedback based on plagiarism score
              $feedback = generatePlagiarismFeedback($plagiarismScore, $matches, $plagiarizedParagraphs);
              
              // Final report
              $report = [
                  'score' => $plagiarismScore,
                  'matches' => $matches,
                  'plagiarized_paragraphs' => $plagiarizedParagraphs,
                  'feedback' => $feedback
              ];
              
              return $report;
          }
          
          // Function to generate feedback based on plagiarism detection
          function generatePlagiarismFeedback($score, $matches, $plagiarizedParagraphs) {
              $feedback = [];
              
              if ($score < 10) {
                  $feedback[] = "The document appears to be mostly original content.";
              } elseif ($score < 30) {
                  $feedback[] = "The document contains some potential matches with existing content. Review highlighted sections and ensure proper citations.";
              } else {
                  $feedback[] = "Significant plagiarism detected. The document contains substantial similarities with existing sources.";
              }
              
              // Source-specific feedback
              if (count($matches) > 0) {
                  $feedback[] = "Potential matching sources:";
                  foreach ($matches as $filename => $match) {
                      $feedback[] = "- $filename: {$match['percentage']}% similarity";
                  }
              }
              
              // Add recommendations
              $feedback[] = "Recommendations:";
              
              if (count($plagiarizedParagraphs) > 0) {
                  $feedback[] = "- Review highlighted paragraphs and add proper citations or paraphrase the content.";
              }
              
              if ($score >= 10) {
                  $feedback[] = "- Use quotation marks for direct quotes and include proper citations.";
                  $feedback[] = "- Paraphrase information from sources using your own words while still citing the source.";
              }
              
              if ($score >= 30) {
                  $feedback[] = "- Consider revising the document to include more original content and analysis.";
              }
              
              return $feedback;
          }
          
          // Process plagiarism detection
          if(isset($_POST['analyze_plagiarism']) || isset($_POST['analyze_combined'])) {
              if(isset($_SESSION['file_content']) && !empty($_SESSION['file_content'])) {
                  $filename = $_SESSION['current_file'];
                  $filetxt = $_SESSION['file_content'];
                  
                  $plagiarism_report = detectPlagiarism($filetxt);
                  $_SESSION['plagiarism_report'] = $plagiarism_report; // Store for combined analysis
                  
                  // Display score with appropriate color
                  $score_class = '';
                  if ($plagiarism_report['score'] >= 30) {
                      $score_class = 'plagiarism-score-high';
                  } elseif ($plagiarism_report['score'] >= 10) {
                      $score_class = 'plagiarism-score-medium';
                  } else {
                      $score_class = 'plagiarism-score-low';
                  }
                  
                  echo '<div class="plagiarism-report">';
                  echo '<h3>Plagiarism Analysis Report</h3>';
                  echo '<div class="plagiarism-score ' . $score_class . '">Plagiarism Score: ' . $plagiarism_report['score'] . '%</div>';
                  
                  echo '<div class="progress">';
                  echo '<div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="' . $plagiarism_report['score'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $plagiarism_report['score'] . '%;">';
                  echo $plagiarism_report['score'] . '%';
                  echo '</div>';
                  echo '</div>';
                  
                  if (!empty($plagiarism_report['matches'])) {
                      echo '<h4>Matching Sources</h4>';
                      echo '<div class="row">';
                      echo '<div class="col-md-12">';
                      echo '<table class="table table-striped">';
                      echo '<thead><tr><th>Source</th><th>Similarity</th></tr></thead>';
                      echo '<tbody>';
                      foreach ($plagiarism_report['matches'] as $filename => $match) {
                          echo '<tr>';
                          echo '<td>' . htmlspecialchars($filename) . '</td>';
                          echo '<td>' . $match['percentage'] . '%</td>';
                          echo '</tr>';
                      }
                      echo '</tbody>';
                      echo '</table>';
                      echo '</div>';
                      echo '</div>';
                      
                      // Display content with highlighted plagiarized paragraphs
                      echo '<h4>Content Analysis</h4>';
                      echo '<p>Paragraphs highlighted in yellow show potential plagiarism:</p>';
                      $paragraphs = explode("\n\n", $filetxt);
                      echo '<div style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd;">';
                      foreach ($paragraphs as $index => $paragraph) {
                          if (isset($plagiarism_report['plagiarized_paragraphs'][$index])) {
                              $sources = [];
                              foreach ($plagiarism_report['plagiarized_paragraphs'][$index] as $match) {
                                  $sources[] = $match['source'] . ' (' . $match['percentage'] . '%)';
                              }
                              echo '<div class="match-source">Similar to: ' . implode(', ', $sources) . '</div>';
                              echo '<p class="plagiarism-highlight">' . htmlspecialchars($paragraph) . '</p>';
                          } else {
                              echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                          }
                      }
                      echo '</div>';
                  } else {
                      echo '<div class="alert alert-success">No significant matching content found.</div>';
                  }
                  
                  echo '<div class="feedback-section">';
                  echo '<h4>Feedback & Recommendations</h4>';
                  echo '<ul>';
                  foreach ($plagiarism_report['feedback'] as $item) {
                      echo '<li>' . $item . '</li>';
                  }
                  echo '</ul>';
                  echo '</div>';
                  
                  // Download report option
                  echo '<form method="post">';
                  echo '<input type="hidden" name="report_filename" value="' . htmlspecialchars($filename) . '">';
                  echo '<input type="hidden" name="report_type" value="plagiarism">';
                  echo '<input type="hidden" name="report_score" value="' . $plagiarism_report['score'] . '">';
                  echo '<button type="submit" name="download_report" class="btn btn-info">Download Plagiarism Report</button>';
                  echo '</form>';
                  
                  echo '</div>'; // end .plagiarism-report
              } else {
                  echo '<div class="alert alert-warning">Please select a file first.</div>';
              }
          }
          ?>
      </div>
      
      <!-- Combined Analysis Tab -->
      <div role="tabpanel" class="tab-pane" id="combined">
          <?php
          if(isset($_POST['analyze_combined']) || (isset($_SESSION['ai_report']) && isset($_SESSION['plagiarism_report']))) {
              if(isset($_SESSION['file_content']) && !empty($_SESSION['file_content'])) {
                  $filename = $_SESSION['current_file'];
                  $filetxt = $_SESSION['file_content'];
                  
                  // Get reports or generate if needed
                  if (!isset($_SESSION['ai_report'])) {
                      $_SESSION['ai_report'] = detectAIContent($filetxt);
                  }
                  if (!isset($_SESSION['plagiarism_report'])) {
                      $_SESSION['plagiarism_report'] = detectPlagiarism($filetxt);
                  }
                  
                  $ai_report = $_SESSION['ai_report'];
                  $plagiarism_report = $_SESSION['plagiarism_report'];
                  
                  echo '<div class="panel panel-default">';
                  echo '<div class="panel-heading"><h3 class="panel-title">Combined Analysis Report for: ' . htmlspecialchars($filename) . '</h3></div>';
                  echo '<div class="panel-body">';
                  
                  // Combined score visualization
                  echo '<div class="row">';
                  echo '<div class="col-md-6">';
                  echo '<h4>AI Content Score: ' . $ai_report['score'] . '%</h4>';
                  echo '<div class="progress">';
                  echo '<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="' . $ai_report['score'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $ai_report['score'] . '%;">';
                  echo $ai_report['score'] . '%';
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
                  
                  echo '<div class="col-md-6">';
                  echo '<h4>Plagiarism Score: ' . $plagiarism_report['score'] . '%</h4>';
                  echo '<div class="progress">';
                  echo '<div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="' . $plagiarism_report['score'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $plagiarism_report['score'] . '%;">';
                  echo $plagiarism_report['score'] . '%';
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
                  
                  // Overall assessment
                  echo '<div class="well">';
                  echo '<h4>Overall Assessment</h4>';
                  
                  if ($ai_report['score'] >= 70 && $plagiarism_report['score'] >= 30) {
                      echo '<div class="alert alert-danger">This document appears to be AI-generated with significant plagiarized content. Recommend thorough revision.</div>';
                  } elseif ($ai_report['score'] >= 70) {
                      echo '<div class="alert alert-warning">This document appears to be primarily AI-generated but contains mostly original content.</div>';
                  } elseif ($plagiarism_report['score'] >= 30) {
                      echo '<div class="alert alert-warning">This document appears to be human-written but contains significant plagiarized content.</div>';
                  } elseif ($ai_report['score'] >= 40 && $plagiarism_report['score'] >= 10) {
                      echo '<div class="alert alert-info">This document shows moderate indicators of both AI generation and some content matches.</div>';
                  } else {
                      echo '<div class="alert alert-success">This document appears to be mostly human-written with minimal content matches.</div>';
                  }
                  echo '</div>';
                  
                  // Content visualization with both types of highlighting
                  echo '<h4>Content Analysis Visualization</h4>';
                  echo '<p>Paragraphs are highlighted according to analysis results:</p>';
                  
                  echo '<div class="highlight-legend">';
                  echo '<span class="ai-highlight">AI-Generated Content</span>';
                  echo '<span class="plagiarism-highlight">Plagiarized Content</span>';
                  echo '</div>';
                  
                  $paragraphs = explode("\n\n", $filetxt);
                  echo '<div style="max-height: 400px; overflow-y: auto; padding: 10px; border: 1px solid #ddd;">';
                  foreach ($paragraphs as $index => $paragraph) {
                      $is_ai = in_array($index, $ai_report['ai_paragraphs']);
                      $is_plagiarized = isset($plagiarism_report['plagiarized_paragraphs'][$index]);
                      
                      if ($is_plagiarized) {
                          $sources = [];
                          foreach ($plagiarism_report['plagiarized_paragraphs'][$index] as $match) {
                              $sources[] = $match['source'] . ' (' . $match['percentage'] . '%)';
                          }
                          echo '<div class="match-source">Similar to: ' . implode(', ', $sources) . '</div>';
                      }
                      
                      if ($is_ai && $is_plagiarized) {
                          echo '<p style="background-color: #ffe0b3;">'; // Blend of both colors
                          echo htmlspecialchars($paragraph);
                          echo ' <span class="label label-danger">AI + Plagiarism</span>';
                          echo '</p>';
                      } elseif ($is_ai) {
                          echo '<p class="ai-highlight">';
                          echo htmlspecialchars($paragraph);
                          echo ' <span class="label label-info">AI</span>';
                          echo '</p>';
                      } elseif ($is_plagiarized) {
                          echo '<p class="plagiarism-highlight">';
                          echo htmlspecialchars($paragraph);
                          echo ' <span class="label label-warning">Plagiarism</span>';
                          echo '</p>';
                      } else {
                          echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                      }
                  }
                  echo '</div>';
                  
                  // Combined recommendations
                  echo '<div class="feedback-section">';
                  echo '<h4>Combined Recommendations</h4>';
                  echo '<ul>';
                  
                  // Add AI feedback
                  foreach (array_slice($ai_report['feedback'], 0, 3) as $item) {
                      echo '<li><strong>AI:</strong> ' . $item . '</li>';
                  }
                  
                  // Add plagiarism feedback
                  foreach (array_slice($plagiarism_report['feedback'], 0, 3) as $item) {
                      echo '<li><strong>Plagiarism:</strong> ' . $item . '</li>';
                  }
                  
                  // Add combined recommendations
                  if ($ai_report['score'] >= 40 && $plagiarism_report['score'] >= 20) {
                      echo '<li><strong>Combined:</strong> This document shows signs of being AI-generated with plagiarized content. Consider rewriting with original ideas and proper citations.</li>';
                  }
                  
                  echo '</ul>';
                  echo '</div>';
                  
                  // Download combined report option
                  echo '<form method="post">';
                  echo '<input type="hidden" name="report_filename" value="' . htmlspecialchars($filename) . '">';
                  echo '<input type="hidden" name="report_type" value="combined">';
                  echo '<input type="hidden" name="ai_score" value="' . $ai_report['score'] . '">';
                  echo '<input type="hidden" name="plagiarism_score" value="' . $plagiarism_report['score'] . '">';
                  echo '<button type="submit" name="download_report" class="btn btn-primary">Download Combined Analysis Report</button>';
                  echo '</form>';
                  
                  echo '</div>'; // end panel-body
                  echo '</div>'; // end panel
              } else {
                  echo '<div class="alert alert-warning">Please select a file first.</div>';
              }
          } else {
              echo '<div class="alert alert-info">Run a combined analysis to view results here.</div>';
          }
          ?>
      </div>
  </div>
</div>

<?php
// Handle report download
if(isset($_POST['download_report'])) {
  $report_filename = $_POST['report_filename'];
  $report_type = $_POST['report_type'];
  
  // Generate report content based on type
  if ($report_type == 'ai') {
      $report_score = $_POST['report_score'];
      $download_filename = 'ai_analysis_' . pathinfo($report_filename, PATHINFO_FILENAME) . '.txt';
      
      $report_content = "AI CONTENT ANALYSIS REPORT\n";
      $report_content .= "=======================\n\n";
      $report_content .= "File analyzed: " . $report_filename . "\n";
      $report_content .= "Analysis date: " . date('Y-m-d H:i:s') . "\n";
      $report_content .= "AI Probability Score: " . $report_score . "%\n\n";
      
      // Add conclusion based on score
      if ($report_score >= 70) {
          $report_content .= "CONCLUSION: High probability of AI-generated content.\n";
      } elseif ($report_score >= 30) {
          $report_content .= "CONCLUSION: Medium probability of AI-generated content or hybrid content.\n";
      } else {
          $report_content .= "CONCLUSION: Low probability of AI-generated content.\n";
      }
      
      // Add recommendations if available
      if (isset($_SESSION['ai_report']['feedback'])) {
          $report_content .= "\nRECOMMENDATIONS:\n";
          foreach ($_SESSION['ai_report']['feedback'] as $feedback) {
              $report_content .= "- " . $feedback . "\n";
          }
      }
  }
  elseif ($report_type == 'plagiarism') {
      $report_score = $_POST['report_score'];
      $download_filename = 'plagiarism_analysis_' . pathinfo($report_filename, PATHINFO_FILENAME) . '.txt';
      
      $report_content = "PLAGIARISM ANALYSIS REPORT\n";
      $report_content .= "==========================\n\n";
      $report_content .= "File analyzed: " . $report_filename . "\n";
      $report_content .= "Analysis date: " . date('Y-m-d H:i:s') . "\n";
      $report_content .= "Plagiarism Score: " . $report_score . "%\n\n";
      
      // Add matching sources if available
      if (isset($_SESSION['plagiarism_report']['matches']) && !empty($_SESSION['plagiarism_report']['matches'])) {
          $report_content .= "MATCHING SOURCES:\n";
          foreach ($_SESSION['plagiarism_report']['matches'] as $source => $match) {
              $report_content .= "- " . $source . ": " . $match['percentage'] . "% similarity\n";
          }
          $report_content .= "\n";
      }
      
      // Add conclusion
      if ($report_score >= 30) {
          $report_content .= "CONCLUSION: Significant plagiarism detected.\n";
      } elseif ($report_score >= 10) {
          $report_content .= "CONCLUSION: Some potential plagiarism detected.\n";
      } else {
          $report_content .= "CONCLUSION: Minimal or no plagiarism detected.\n";
      }
      
      // Add recommendations if available
      if (isset($_SESSION['plagiarism_report']['feedback'])) {
          $report_content .= "\nRECOMMENDATIONS:\n";
          foreach ($_SESSION['plagiarism_report']['feedback'] as $feedback) {
              $report_content .= "- " . $feedback . "\n";
          }
      }
  }
  else { // combined
      $ai_score = $_POST['ai_score'];
      $plagiarism_score = $_POST['plagiarism_score'];
      $download_filename = 'combined_analysis_' . pathinfo($report_filename, PATHINFO_FILENAME) . '.txt';
      
      $report_content = "COMBINED ANALYSIS REPORT\n";
      $report_content .= "=======================\n\n";
      $report_content .= "File analyzed: " . $report_filename . "\n";
      $report_content .= "Analysis date: " . date('Y-m-d H:i:s') . "\n";
      $report_content .= "AI Probability Score: " . $ai_score . "%\n";
      $report_content .= "Plagiarism Score: " . $plagiarism_score . "%\n\n";
      
      // Add overall assessment
      $report_content .= "OVERALL ASSESSMENT:\n";
      if ($ai_score >= 70 && $plagiarism_score >= 30) {
          $report_content .= "This document appears to be AI-generated with significant plagiarized content.\n";
      } elseif ($ai_score >= 70) {
          $report_content .= "This document appears to be primarily AI-generated but contains mostly original content.\n";
      } elseif ($plagiarism_score >= 30) {
          $report_content .= "This document appears to be human-written but contains significant plagiarized content.\n";
      } elseif ($ai_score >= 40 && $plagiarism_score >= 10) {
          $report_content .= "This document shows moderate indicators of both AI generation and some content matches.\n";
      } else {
          $report_content .= "This document appears to be mostly human-written with minimal content matches.\n";
      }
      
      // Add combined recommendations
      $report_content .= "\nRECOMMENDATIONS:\n";
      if (isset($_SESSION['ai_report']['feedback'])) {
          $report_content .= "AI CONTENT:\n";
          foreach (array_slice($_SESSION['ai_report']['feedback'], 0, 3) as $feedback) {
              $report_content .= "- " . $feedback . "\n";
          }
      }
      
      if (isset($_SESSION['plagiarism_report']['feedback'])) {
          $report_content .= "\nPLAGIARISM:\n";
          foreach (array_slice($_SESSION['plagiarism_report']['feedback'], 0, 3) as $feedback) {
              $report_content .= "- " . $feedback . "\n";
          }
      }
  }
    ob_clean();

  
  // Force download
  header('Content-Type: text/plain');
  header('Content-Disposition: attachment; filename="' . $download_filename . '"');
  header('Content-Length: ' . strlen($report_content));
  echo $report_content;
  exit;
}
?>

<script>
$(document).ready(function() {
  // Click functionality for file list items
  $('.list-group-item').click(function() {
      $('#fname').val($(this).text());
  });
  
  // Maintain active tab after form submission
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      localStorage.setItem('lastTab', $(e.target).attr('href'));
  });
  
  var lastTab = localStorage.getItem('lastTab');
  if (lastTab) {
      $('a[href="' + lastTab + '"]').tab('show');
  }
});
</script>
</body>
</html>