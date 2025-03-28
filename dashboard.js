// DOM Elements
const sidebar = document.querySelector('.sidebar');
const btnMenu = document.querySelector('.btn-menu');
const main = document.querySelector('main');
const navLinks = document.querySelectorAll('.nav-links li');
const pages = document.querySelectorAll('.page-content');
const logoutBtn = document.getElementById('logout-btn');
const logoutModal = document.getElementById('logout-modal');
const cancelLogoutBtn = document.getElementById('cancel-logout-btn');
const confirmLogoutBtn = document.getElementById('confirm-logout-btn');
const closeModalBtns = document.querySelectorAll('.close-modal');
const calculationModal = document.getElementById('calculation-modal');
const calculatorForm = document.getElementById('calculator-form');
const incomeSlider = document.getElementById('income-slider');
const incomeInput = document.getElementById('income');
const incomeDisplay = document.getElementById('income-display');
const deductionsInput = document.getElementById('deductions');
const deductionsDisplay = document.getElementById('deductions-display');
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');
const reportTypeSelect = document.getElementById('report-type');
const quarterGroup = document.getElementById('quarter-group');
const historyTable = document.getElementById('history-table');
const yearBtns = document.querySelectorAll('.year-btn');
const historySearch = document.getElementById('history-search');
const resultsCount = document.getElementById('results-count');
const savePdfBtn = document.getElementById('save-pdf-btn');
const downloadResultsBtn = document.getElementById('download-results-btn');
const editProfileBtn = document.getElementById('edit-profile-btn');
const saveProfileBtn = document.getElementById('save-profile-btn');

// Toggle sidebar - Fixed to properly show menu items
btnMenu.addEventListener('click', function(e) {
  e.preventDefault();
  sidebar.classList.toggle('close');
  main.classList.toggle('expanded');
  
  // For mobile view, ensure the sidebar is visible when toggled
  if (window.innerWidth <= 576) {
    if (sidebar.classList.contains('close')) {
      sidebar.style.width = '260px';
      sidebar.style.padding = '10px';
    } else {
      sidebar.style.width = '0';
      sidebar.style.padding = '0';
    }
  }
});

// Navigation
function navigateTo(pageId) {
  // Hide all pages
  pages.forEach(page => {
    page.classList.remove('active');
  });
  
  // Show selected page
  const selectedPage = document.getElementById(`${pageId}-page`);
  if (selectedPage) {
    selectedPage.classList.add('active');
  }
  
  // Update active nav link
  navLinks.forEach(link => {
    link.classList.remove('active');
    if (link.dataset.page === pageId) {
      link.classList.add('active');
    }
  });
  
  // Show save PDF button only on calculator results page
  if (pageId === 'calculator' && document.getElementById('calculator-results-tab').classList.contains('active')) {
    savePdfBtn.style.display = 'block';
  } else {
    savePdfBtn.style.display = 'none';
  }
  
  // Close sidebar on mobile after navigation
  if (window.innerWidth <= 576) {
    sidebar.classList.add('close');
    main.classList.remove('expanded');
  }
}

// Set up navigation event listeners
navLinks.forEach(link => {
  link.addEventListener('click', () => {
    const pageId = link.dataset.page;
    if (pageId) {
      navigateTo(pageId);
    }
  });
});

// Logout Modal
logoutBtn.addEventListener('click', () => {
  logoutModal.classList.add('active');
});

cancelLogoutBtn.addEventListener('click', () => {
  logoutModal.classList.remove('active');
});

confirmLogoutBtn.addEventListener('click', () => {
  window.location.href = 'logout.php';
});

// Close modals
closeModalBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.modal').forEach(modal => {
      modal.classList.remove('active');
    });
  });
});

// Income slider and input sync
if (incomeSlider && incomeInput && incomeDisplay) {
  incomeSlider.addEventListener('input', () => {
    incomeInput.value = incomeSlider.value;
    incomeDisplay.textContent = formatCurrency(incomeSlider.value);
  });
  
  incomeInput.addEventListener('input', () => {
    if (incomeInput.value > 250000) {
      incomeInput.value = 250000;
    }
    incomeSlider.value = incomeInput.value;
    incomeDisplay.textContent = formatCurrency(incomeInput.value);
  });
}

// Deductions input formatting
if (deductionsInput && deductionsDisplay) {
  deductionsInput.addEventListener('input', () => {
    deductionsDisplay.textContent = formatCurrency(deductionsInput.value);
  });
}

// Format currency
function formatCurrency(value) {
  return '$' + parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  });
}

// Tab switching
tabBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    if (!btn.disabled) {
      const tabId = btn.dataset.tab;
      
      // Deactivate all tabs
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      
      // Activate selected tab
      btn.classList.add('active');
      document.getElementById(tabId + '-tab').classList.add('active');
      
      // Show save PDF button if on results tab
      if (tabId === 'calculator-results') {
        savePdfBtn.style.display = 'block';
      } else {
        savePdfBtn.style.display = 'none';
      }
    }
  });
});

// Report type change
if (reportTypeSelect && quarterGroup) {
  reportTypeSelect.addEventListener('change', () => {
    if (reportTypeSelect.value === 'quarterly') {
      quarterGroup.style.display = 'block';
    } else {
      quarterGroup.style.display = 'none';
    }
  });
}

// Tax calculator form submission
if (calculatorForm) {
  calculatorForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    // Get form values
    const income = parseFloat(incomeInput.value);
    const deductions = parseFloat(deductionsInput.value);
    const filingStatus = document.getElementById('filingStatus').value;
    
    // Calculate tax
    const taxableIncome = Math.max(0, income - deductions);
    const taxResult = calculateTax(taxableIncome, filingStatus);
    
    // Display results
    document.getElementById('tax-amount').textContent = formatCurrency(taxResult.totalTax);
    document.getElementById('effective-rate').textContent = taxResult.effectiveRate.toFixed(2) + '%';
    document.getElementById('marginal-rate').textContent = taxResult.marginalRate.toFixed(2) + '%';
    document.getElementById('calculation-summary').textContent = 
      `Based on your ${formatCurrency(income)} income with ${formatCurrency(deductions)} in deductions`;
    
    // Generate brackets breakdown
    generateBracketsBreakdown(taxableIncome, filingStatus);
    
    // Switch to results tab
    document.querySelector('.tab-btn[data-tab="calculator-results"]').disabled = false;
    document.querySelector('.tab-btn[data-tab="calculator-results"]').click();
  });
}

// Calculate tax based on 2023 tax brackets (simplified for demo)
function calculateTax(taxableIncome, filingStatus) {
  let brackets;
  
  // Define tax brackets based on filing status
  if (filingStatus === 'single') {
    brackets = [
      { rate: 10, min: 0, max: 11000 },
      { rate: 12, min: 11000, max: 44725 },
      { rate: 22, min: 44725, max: 95375 },
      { rate: 24, min: 95375, max: 182100 },
      { rate: 32, min: 182100, max: 231250 },
      { rate: 35, min: 231250, max: 578125 },
      { rate: 37, min: 578125, max: Infinity }
    ];
  } else if (filingStatus === 'married') {
    brackets = [
      { rate: 10, min: 0, max: 22000 },
      { rate: 12, min: 22000, max: 89450 },
      { rate: 22, min: 89450, max: 190750 },
      { rate: 24, min: 190750, max: 364200 },
      { rate: 32, min: 364200, max: 462500 },
      { rate: 35, min: 462500, max: 693750 },
      { rate: 37, min: 693750, max: Infinity }
    ];
  } else { // head of household
    brackets = [
      { rate: 10, min: 0, max: 15700 },
      { rate: 12, min: 15700, max: 59850 },
      { rate: 22, min: 59850, max: 95350 },
      { rate: 24, min: 95350, max: 182100 },
      { rate: 32, min: 182100, max: 231250 },
      { rate: 35, min: 231250, max: 578100 },
      { rate: 37, min: 578100, max: Infinity }
    ];
  }
  
  // Calculate tax for each bracket
  let totalTax = 0;
  let marginalRate = 0;
  
  for (const bracket of brackets) {
    if (taxableIncome > bracket.min) {
      const taxableAmountInBracket = Math.min(taxableIncome, bracket.max) - bracket.min;
      totalTax += (taxableAmountInBracket * bracket.rate) / 100;
      marginalRate = bracket.rate;
    }
  }
  
  // Calculate effective tax rate
  const effectiveRate = (totalTax / taxableIncome) * 100 || 0;
  
  return {
    totalTax,
    effectiveRate,
    marginalRate,
    brackets
  };
}

// Generate tax brackets breakdown
function generateBracketsBreakdown(taxableIncome, filingStatus) {
  const bracketsContainer = document.getElementById('brackets-container');
  bracketsContainer.innerHTML = '';
  
  const taxResult = calculateTax(taxableIncome, filingStatus);
  
  taxResult.brackets.forEach(bracket => {
    if (taxableIncome > bracket.min) {
      const taxableAmountInBracket = Math.min(taxableIncome, bracket.max) - bracket.min;
      const taxInBracket = (taxableAmountInBracket * bracket.rate) / 100;
      
      const bracketRow = document.createElement('div');
      bracketRow.className = 'bracket-row';
      bracketRow.innerHTML = `
        <div>${bracket.rate}%</div>
        <div>${formatCurrency(taxableAmountInBracket)}</div>
        <div>${formatCurrency(taxInBracket)}</div>
      `;
      
      bracketsContainer.appendChild(bracketRow);
    }
  });
}

// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Filter history by year
yearBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    yearBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    // Filter logic would go here in a real application
    // For demo, just repopulate the table
    populateHistoryTable();
  });
});

// Search history
if (historySearch) {
  historySearch.addEventListener('input', () => {
    // Search logic would go here in a real application
    // For demo, just repopulate the table
    populateHistoryTable();
  });
}

// PDF download buttons
if (downloadResultsBtn) {
  downloadResultsBtn.addEventListener('click', () => {
    alert('PDF download functionality would be implemented here.');
  });
}

if (savePdfBtn) {
  savePdfBtn.addEventListener('click', () => {
    alert('PDF download functionality would be implemented here.');
  });
}
if (editProfileBtn) {
  editProfileBtn.addEventListener('click', function() {
    // Enable all form inputs
    const inputs = document.querySelectorAll('#personal-form input');
    inputs.forEach(input => {
      input.disabled = false;
    });
    
    // Show save button
    if (saveProfileBtn) {
      saveProfileBtn.style.display = 'block';
    }
  });
}

if (saveProfileBtn) {
  saveProfileBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const inputs = document.querySelectorAll('#personal-form input');
    inputs.forEach(input => {
      input.disabled = true;
    });
    saveProfileBtn.style.display = 'none';
    alert('Profile updated successfully!');
  });
}



document.addEventListener('DOMContentLoaded', () => {
  if (window.innerWidth <= 576) {
    sidebar.style.width = '0';
    sidebar.style.padding = '0';
    main.style.left = '0';
    main.style.width = '100%';
  }
  populateHistoryTable();
  if (incomeDisplay && incomeSlider) {
    incomeDisplay.textContent = formatCurrency(incomeSlider.value);
  }
  if (deductionsDisplay && deductionsInput) {
    deductionsDisplay.textContent = formatCurrency(deductionsInput.value);
  }
});
document.head.insertAdjacentHTML('beforeend', `
  <style>
    @media (max-width: 576px) {
      .btn-menu {
        position: fixed;
        top: 20px;
        left: 20px;
        background-color: var(--color-primary);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 101;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        color: var(--color-secondary);
      }
      .sidebar {
        z-index: 100;
        transition: all 0.3s ease;
      }
      .sidebar.close {
        width: 260px !important;
        padding: 10px !important;
      }
    }
  </style>
`);