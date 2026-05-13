<?php
exec('python generate_embeddings.py 2>&1', $output, $return_code);
echo "<pre>";
foreach ($output as $line) echo htmlspecialchars($line) . "\n";
echo "</pre>";
echo $return_code === 0 ? "✅ Embeddings generated successfully." : "❌ Error generating embeddings.";
?>