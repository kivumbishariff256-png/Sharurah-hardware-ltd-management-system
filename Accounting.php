<?php
/**
 * Accounting Management Class for Sharurah Hardware Ltd Management System
 * File: classes/Accounting.php
 */

require_once __DIR__ . '/../config/database.php';

class Accounting {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Get chart of accounts
    public function getChartOfAccounts() {
        $query = "SELECT * FROM chart_of_accounts WHERE is_active = TRUE ORDER BY account_code ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get account by ID
    public function getAccountById($account_id) {
        $query = "SELECT * FROM chart_of_accounts WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create journal entry
    public function createJournalEntry($data) {
        try {
            $this->db->beginTransaction();
            
            // Generate entry number
            $entry_number = $this->generateEntryNumber();
            
            // Calculate totals
            $total_debit = 0;
            $total_credit = 0;
            
            foreach ($data['lines'] as $line) {
                $total_debit += $line['debit_amount'];
                $total_credit += $line['credit_amount'];
            }
            
            // Insert journal entry header
            $query = "INSERT INTO journal_entries (entry_number, entry_date, reference_number, 
                                                  description, entry_type, total_debit, total_credit, 
                                                  status, created_by) 
                      VALUES (:entry_number, :entry_date, :reference_number, 
                              :description, :entry_type, :total_debit, :total_credit, 
                              :status, :created_by)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":entry_number", $entry_number);
            $stmt->bindParam(":entry_date", $data['entry_date']);
            $stmt->bindParam(":reference_number", $data['reference_number']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":entry_type", $data['entry_type']);
            $stmt->bindParam(":total_debit", $total_debit);
            $stmt->bindParam(":total_credit", $total_credit);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":created_by", $data['created_by']);
            
            $stmt->execute();
            $entry_id = $this->db->lastInsertId();
            
            // Insert journal entry lines
            foreach ($data['lines'] as $line) {
                $line_query = "INSERT INTO journal_entry_lines (entry_id, account_id, description, 
                                                                debit_amount, credit_amount) 
                              VALUES (:entry_id, :account_id, :description, 
                                      :debit_amount, :credit_amount)";
                
                $line_stmt = $this->db->prepare($line_query);
                $line_stmt->bindParam(":entry_id", $entry_id);
                $line_stmt->bindParam(":account_id", $line['account_id']);
                $line_stmt->bindParam(":description", $line['description']);
                $line_stmt->bindParam(":debit_amount", $line['debit_amount']);
                $line_stmt->bindParam(":credit_amount", $line['credit_amount']);
                $line_stmt->execute();
                
                // Update account balance
                $this->updateAccountBalance($line['account_id'], $line['debit_amount'], $line['credit_amount']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'entry_id' => $entry_id,
                'entry_number' => $entry_number
            ];
            
        } catch(PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Generate unique entry number
    private function generateEntryNumber() {
        $prefix = 'JE';
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
    
    // Update account balance
    private function updateAccountBalance($account_id, $debit_amount, $credit_amount) {
        // Get account type
        $query = "SELECT account_type FROM chart_of_accounts WHERE account_id = :account_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();
        
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            $balance_change = 0;
            
            // For assets and expenses: debit increases, credit decreases
            if (in_array($account['account_type'], ['asset', 'expense'])) {
                $balance_change = $debit_amount - $credit_amount;
            }
            // For liabilities, equity, and revenue: credit increases, debit decreases
            else {
                $balance_change = $credit_amount - $debit_amount;
            }
            
            $update_query = "UPDATE chart_of_accounts 
                            SET current_balance = current_balance + :balance_change 
                            WHERE account_id = :account_id";
            
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindParam(":balance_change", $balance_change);
            $update_stmt->bindParam(":account_id", $account_id);
            $update_stmt->execute();
        }
    }
    
    // Get journal entries
    public function getJournalEntries($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT je.*, u.full_name as created_by_name 
                  FROM journal_entries je
                  JOIN users u ON je.created_by = u.user_id";
        
        if ($status) {
            $query .= " WHERE je.status = :status";
        }
        
        $query .= " ORDER BY je.entry_date DESC, je.entry_id DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get journal entry by ID
    public function getJournalEntryById($entry_id) {
        $query = "SELECT je.*, u.full_name as created_by_name 
                  FROM journal_entries je
                  JOIN users u ON je.created_by = u.user_id
                  WHERE je.entry_id = :entry_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":entry_id", $entry_id);
        $stmt->execute();
        
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entry) {
            // Get entry lines
            $lines_query = "SELECT jel.*, coa.account_code, coa.account_name 
                           FROM journal_entry_lines jel
                           JOIN chart_of_accounts coa ON jel.account_id = coa.account_id
                           WHERE jel.entry_id = :entry_id";
            
            $lines_stmt = $this->db->prepare($lines_query);
            $lines_stmt->bindParam(":entry_id", $entry_id);
            $lines_stmt->execute();
            
            $entry['lines'] = $lines_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $entry;
    }
    
    // Post journal entry
    public function postJournalEntry($entry_id, $user_id) {
        $query = "UPDATE journal_entries 
                  SET status = 'posted', posted_at = NOW() 
                  WHERE entry_id = :entry_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":entry_id", $entry_id);
        
        return ['success' => $stmt->execute()];
    }
    
    // Create expense
    public function createExpense($data) {
        // Generate expense number
        $expense_number = $this->generateExpenseNumber();
        
        $query = "INSERT INTO expenses (expense_number, expense_date, category, description, 
                                       amount, payment_method, reference_number, receipt_image_url, 
                                       status, created_by) 
                  VALUES (:expense_number, :expense_date, :category, :description, 
                          :amount, :payment_method, :reference_number, :receipt_image_url, 
                          :status, :created_by)";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":expense_number", $expense_number);
            $stmt->bindParam(":expense_date", $data['expense_date']);
            $stmt->bindParam(":category", $data['category']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":amount", $data['amount']);
            $stmt->bindParam(":payment_method", $data['payment_method']);
            $stmt->bindParam(":reference_number", $data['reference_number']);
            $stmt->bindParam(":receipt_image_url", $data['receipt_image_url']);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":created_by", $data['created_by']);
            
            if ($stmt->execute()) {
                $expense_id = $this->db->lastInsertId();
                return ['success' => true, 'expense_id' => $expense_id, 'expense_number' => $expense_number];
            }
            
            return ['success' => false, 'message' => 'Failed to create expense'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Generate unique expense number
    private function generateExpenseNumber() {
        $prefix = 'EXP';
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }
    
    // Get expenses
    public function getExpenses($limit = 50, $offset = 0, $status = null) {
        $query = "SELECT e.*, u.full_name as created_by_name, 
                         CASE WHEN e.approved_by IS NOT NULL THEN 
                             (SELECT full_name FROM users WHERE user_id = e.approved_by) 
                         END as approved_by_name
                  FROM expenses e
                  JOIN users u ON e.created_by = u.user_id";
        
        if ($status) {
            $query .= " WHERE e.status = :status";
        }
        
        $query .= " ORDER BY e.expense_date DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        
        if ($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Approve expense
    public function approveExpense($expense_id, $approved_by) {
        $query = "UPDATE expenses 
                  SET status = 'approved', approved_by = :approved_by 
                  WHERE expense_id = :expense_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":approved_by", $approved_by);
        $stmt->bindParam(":expense_id", $expense_id);
        
        return ['success' => $stmt->execute()];
    }
    
    // Get financial summary
    public function getFinancialSummary($start_date = null, $end_date = null) {
        // Get revenue
        $revenue_query = "SELECT SUM(total_amount) as total_revenue 
                         FROM sales_orders 
                         WHERE payment_status = 'paid'";
        
        if ($start_date && $end_date) {
            $revenue_query .= " AND DATE(order_date) BETWEEN :start_date AND :end_date";
        }
        
        $revenue_stmt = $this->db->prepare($revenue_query);
        
        if ($start_date && $end_date) {
            $revenue_stmt->bindParam(":start_date", $start_date);
            $revenue_stmt->bindParam(":end_date", $end_date);
        }
        
        $revenue_stmt->execute();
        $revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
        
        // Get expenses
        $expense_query = "SELECT SUM(amount) as total_expenses 
                         FROM expenses 
                         WHERE status = 'approved'";
        
        if ($start_date && $end_date) {
            $expense_query .= " AND DATE(expense_date) BETWEEN :start_date AND :end_date";
        }
        
        $expense_stmt = $this->db->prepare($expense_query);
        
        if ($start_date && $end_date) {
            $expense_stmt->bindParam(":start_date", $start_date);
            $expense_stmt->bindParam(":end_date", $end_date);
        }
        
        $expense_stmt->execute();
        $expenses = $expense_stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'] ?? 0;
        
        // Get pending payments
        $pending_query = "SELECT SUM(total_amount) as pending_amount 
                         FROM sales_orders 
                         WHERE payment_status IN ('pending', 'partial')";
        
        $pending_stmt = $this->db->prepare($pending_query);
        $pending_stmt->execute();
        $pending = $pending_stmt->fetch(PDO::FETCH_ASSOC)['pending_amount'] ?? 0;
        
        // Get account balances
        $accounts_query = "SELECT account_type, SUM(current_balance) as total_balance 
                          FROM chart_of_accounts 
                          WHERE is_active = TRUE 
                          GROUP BY account_type";
        
        $accounts_stmt = $this->db->prepare($accounts_query);
        $accounts_stmt->execute();
        $accounts = $accounts_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_revenue' => $revenue,
            'total_expenses' => $expenses,
            'net_profit' => $revenue - $expenses,
            'pending_payments' => $pending,
            'account_balances' => $accounts
        ];
    }
    
    // Get profit and loss statement
    public function getProfitAndLoss($start_date, $end_date) {
        $revenue_query = "SELECT SUM(total_amount) as revenue 
                         FROM sales_orders 
                         WHERE DATE(order_date) BETWEEN :start_date AND :end_date 
                         AND payment_status = 'paid'";
        
        $revenue_stmt = $this->db->prepare($revenue_query);
        $revenue_stmt->bindParam(":start_date", $start_date);
        $revenue_stmt->bindParam(":end_date", $end_date);
        $revenue_stmt->execute();
        
        $revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        
        $expense_query = "SELECT category, SUM(amount) as amount 
                         FROM expenses 
                         WHERE DATE(expense_date) BETWEEN :start_date AND :end_date 
                         AND status = 'approved'
                         GROUP BY category";
        
        $expense_stmt = $this->db->prepare($expense_query);
        $expense_stmt->bindParam(":start_date", $start_date);
        $expense_stmt->bindParam(":end_date", $end_date);
        $expense_stmt->execute();
        
        $expenses = $expense_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_expenses = array_sum(array_column($expenses, 'amount'));
        
        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_expenses' => $total_expenses,
            'net_profit' => $revenue - $total_expenses,
            'profit_margin' => $revenue > 0 ? (($revenue - $total_expenses) / $revenue) * 100 : 0
        ];
    }
}
?>