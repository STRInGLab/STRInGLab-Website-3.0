<?php
class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $timeWindow;
    
    public function __construct($db_connection, $maxAttempts = 3, $timeWindow = 3600) {
        $this->conn = $db_connection;
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow; // 1 hour in seconds
    }
    
    public function isAllowed($ip) {
        // Clean old entries
        $this->cleanOldEntries();
        
        // Check current attempts
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM rate_limiting WHERE ip = ? AND timestamp > ?");
        $cutoff = time() - $this->timeWindow;
        $stmt->bind_param("si", $ip, $cutoff);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['attempts'] < $this->maxAttempts;
    }
    
    public function recordAttempt($ip) {
        $stmt = $this->conn->prepare("INSERT INTO rate_limiting (ip, timestamp) VALUES (?, ?)");
        $timestamp = time();
        $stmt->bind_param("si", $ip, $timestamp);
        $stmt->execute();
    }
    
    private function cleanOldEntries() {
        $cutoff = time() - $this->timeWindow;
        $stmt = $this->conn->prepare("DELETE FROM rate_limiting WHERE timestamp < ?");
        $stmt->bind_param("i", $cutoff);
        $stmt->execute();
    }
    
    public function getRemainingTime($ip) {
        $stmt = $this->conn->prepare("SELECT MIN(timestamp) as oldest FROM rate_limiting WHERE ip = ? ORDER BY timestamp LIMIT ?");
        $stmt->bind_param("si", $ip, $this->maxAttempts);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['oldest']) {
            return ($row['oldest'] + $this->timeWindow) - time();
        }
        return 0;
    }
}

// Create rate limiting table (run this once)
function createRateLimitingTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS rate_limiting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        timestamp INT NOT NULL,
        INDEX ip_time_idx (ip, timestamp)
    )";
    $conn->query($sql);
}
?>
