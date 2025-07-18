// Market functionality for the browser game

let marketLevel = 0;

// Check if player has a market and what level
function checkMarketAccess() {
    fetch(`php/backend.php?settlementId=${settlementId}&buildingType=Markt`)
        .then(response => response.json())
        .then(data => {
            if (data.building && data.building.level > 0) {
                marketLevel = data.building.level;
                document.getElementById('marketLevel').textContent = marketLevel;
                document.getElementById('marketAccess').style.display = 'none';
                document.getElementById('marketInterface').style.display = 'block';
                loadMarketData();
            } else {
                marketLevel = 0;
                document.getElementById('marketLevel').textContent = '0';
                document.getElementById('marketAccess').style.display = 'block';
                document.getElementById('marketInterface').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking market access:', error);
            // Show interface anyway for testing
            document.getElementById('marketInterface').style.display = 'block';
        });
}

// Load all market data (offers, history, etc.)
function loadMarketData() {
    refreshOffers();
    loadMyOffers();
    loadTradeHistory();
}

// Create a new trade offer
function createTradeOffer() {
    const offerType = document.getElementById('offerType').value;
    const offerWood = parseFloat(document.getElementById('offerWood').value) || 0;
    const offerStone = parseFloat(document.getElementById('offerStone').value) || 0;
    const offerOre = parseFloat(document.getElementById('offerOre').value) || 0;
    const offerGold = parseFloat(document.getElementById('offerGold').value) || 0;
    
    const requestWood = parseFloat(document.getElementById('requestWood').value) || 0;
    const requestStone = parseFloat(document.getElementById('requestStone').value) || 0;
    const requestOre = parseFloat(document.getElementById('requestOre').value) || 0;
    const requestGold = parseFloat(document.getElementById('requestGold').value) || 0;
    
    const maxTrades = parseInt(document.getElementById('maxTrades').value) || 1;

    // Validation
    const totalOffered = offerWood + offerStone + offerOre + offerGold;
    const totalRequested = requestWood + requestStone + requestOre + requestGold;
    
    if (totalOffered === 0) {
        alert('You must offer something!');
        return;
    }
    
    if (totalRequested === 0) {
        alert('You must request something in return!');
        return;
    }

    const offerData = {
        offerType,
        offerWood,
        offerStone,
        offerOre,
        offerGold,
        requestWood,
        requestStone,
        requestOre,
        requestGold,
        maxTrades
    };

    fetch(`php/market-backend.php?settlementId=${settlementId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'createOffer', ...offerData }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Trade offer created successfully!');
            clearOfferForm();
            loadMarketData();
        } else {
            alert(data.message || 'Failed to create offer');
        }
    })
    .catch(error => {
        console.error('Error creating offer:', error);
        alert('An error occurred while creating the offer.');
    });
}

// Clear the offer creation form
function clearOfferForm() {
    document.getElementById('offerWood').value = 0;
    document.getElementById('offerStone').value = 0;
    document.getElementById('offerOre').value = 0;
    document.getElementById('offerGold').value = 0;
    document.getElementById('requestWood').value = 0;
    document.getElementById('requestStone').value = 0;
    document.getElementById('requestOre').value = 0;
    document.getElementById('requestGold').value = 0;
    document.getElementById('maxTrades').value = 1;
}

// Load and display available trade offers
function refreshOffers() {
    const filter = document.getElementById('offerFilter').value;
    
    fetch(`php/market-backend.php?settlementId=${settlementId}&getOffers=true&filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            const offersList = document.getElementById('offersList');
            
            if (data.offers && data.offers.length > 0) {
                offersList.innerHTML = data.offers.map(offer => createOfferHTML(offer, false)).join('');
            } else {
                offersList.innerHTML = '<p>No trade offers available.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading offers:', error);
            document.getElementById('offersList').innerHTML = '<p>Error loading offers.</p>';
        });
}

// Load player's own offers
function loadMyOffers() {
    fetch(`php/market-backend.php?settlementId=${settlementId}&getMyOffers=true`)
        .then(response => response.json())
        .then(data => {
            const myOffersList = document.getElementById('myOffersList');
            
            if (data.offers && data.offers.length > 0) {
                myOffersList.innerHTML = data.offers.map(offer => createOfferHTML(offer, true)).join('');
            } else {
                myOffersList.innerHTML = '<p>You have no active offers.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading my offers:', error);
            document.getElementById('myOffersList').innerHTML = '<p>Error loading your offers.</p>';
        });
}

// Load trade history
function loadTradeHistory() {
    fetch(`php/market-backend.php?settlementId=${settlementId}&getHistory=true`)
        .then(response => response.json())
        .then(data => {
            const historyList = document.getElementById('tradeHistory');
            
            if (data.history && data.history.length > 0) {
                historyList.innerHTML = data.history.map(trade => createHistoryHTML(trade)).join('');
            } else {
                historyList.innerHTML = '<p>No recent trades.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading trade history:', error);
            document.getElementById('tradeHistory').innerHTML = '<p>Error loading trade history.</p>';
        });
}

// Create HTML for a trade offer
function createOfferHTML(offer, isMyOffer) {
    const offerTypeNames = {
        'resource_trade': 'Resource Trade',
        'resource_sell': 'Selling Resources',
        'resource_buy': 'Buying Resources'
    };

    const offeredResources = [];
    if (offer.offerWood > 0) offeredResources.push(`${getResourceEmoji('wood')} ${formatNumber(offer.offerWood)}`);
    if (offer.offerStone > 0) offeredResources.push(`${getResourceEmoji('stone')} ${formatNumber(offer.offerStone)}`);
    if (offer.offerOre > 0) offeredResources.push(`${getResourceEmoji('ore')} ${formatNumber(offer.offerOre)}`);
    if (offer.offerGold > 0) offeredResources.push(`${getResourceEmoji('gold')} ${formatNumber(offer.offerGold)}`);

    const requestedResources = [];
    if (offer.requestWood > 0) requestedResources.push(`${getResourceEmoji('wood')} ${formatNumber(offer.requestWood)}`);
    if (offer.requestStone > 0) requestedResources.push(`${getResourceEmoji('stone')} ${formatNumber(offer.requestStone)}`);
    if (offer.requestOre > 0) requestedResources.push(`${getResourceEmoji('ore')} ${formatNumber(offer.requestOre)}`);
    if (offer.requestGold > 0) requestedResources.push(`${getResourceEmoji('gold')} ${formatNumber(offer.requestGold)}`);

    const remainingTrades = offer.maxTrades - offer.currentTrades;
    
    const actionButton = isMyOffer 
        ? `<button class="cancel-offer-btn" onclick="cancelOffer(${offer.offerId})">Cancel</button>`
        : `<button class="accept-offer-btn" onclick="acceptOffer(${offer.offerId})">Accept Trade</button>`;

    return `
        <div class="offer-item">
            <div class="offer-header">
                <div>
                    <span class="offer-type-badge">${offerTypeNames[offer.offerType]}</span>
                    <span>by ${offer.playerName || 'Unknown Player'}</span>
                </div>
                <div>Remaining: ${remainingTrades}/${offer.maxTrades}</div>
            </div>
            <div class="offer-resources">
                <div class="resources-offered">
                    <strong>Offering:</strong>
                    ${offeredResources.map(r => `<span class="resource-amount">${r}</span>`).join('')}
                </div>
                <div class="trade-arrow">${getUIEmoji('arrow_right')}</div>
                <div class="resources-wanted">
                    <strong>Wants:</strong>
                    ${requestedResources.map(r => `<span class="resource-amount">${r}</span>`).join('')}
                </div>
            </div>
            <div style="margin-top: 10px; text-align: right;">
                ${actionButton}
            </div>
        </div>
    `;
}

// Create HTML for trade history entry
function createHistoryHTML(trade) {
    return `
        <div class="offer-item">
            <div class="offer-header">
                <div>Trade completed at ${new Date(trade.completedAt).toLocaleString()}</div>
                <div>With: ${trade.otherPlayerName}</div>
            </div>
            <div class="offer-resources">
                <div class="resources-offered">
                    <strong>You gave:</strong>
                    <span class="resource-amount">${getResourceEmoji('wood')} ${formatNumber(trade.yourGave?.wood || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('stone')} ${formatNumber(trade.yourGave?.stone || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('ore')} ${formatNumber(trade.yourGave?.ore || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('gold')} ${formatNumber(trade.yourGave?.gold || 0)}</span>
                </div>
                <div class="trade-arrow">${getUIEmoji('arrow_bidirectional')}</div>
                <div class="resources-wanted">
                    <strong>You got:</strong>
                    <span class="resource-amount">${getResourceEmoji('wood')} ${formatNumber(trade.youGot?.wood || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('stone')} ${formatNumber(trade.youGot?.stone || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('ore')} ${formatNumber(trade.youGot?.ore || 0)}</span>
                    <span class="resource-amount">${getResourceEmoji('gold')} ${formatNumber(trade.youGot?.gold || 0)}</span>
                </div>
            </div>
        </div>
    `;
}

// Accept a trade offer
function acceptOffer(offerId) {
    if (!confirm('Are you sure you want to accept this trade offer?')) {
        return;
    }

    fetch(`php/market-backend.php?settlementId=${settlementId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'acceptOffer', offerId }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Trade completed successfully!');
            loadMarketData();
            // Update resources display
            fetchResources(settlementId);
        } else {
            alert(data.message || 'Failed to complete trade');
        }
    })
    .catch(error => {
        console.error('Error accepting offer:', error);
        alert('An error occurred while completing the trade.');
    });
}

// Cancel a trade offer
function cancelOffer(offerId) {
    if (!confirm('Are you sure you want to cancel this offer?')) {
        return;
    }

    fetch(`php/market-backend.php?settlementId=${settlementId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'cancelOffer', offerId }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Offer cancelled successfully!');
            loadMarketData();
        } else {
            alert(data.message || 'Failed to cancel offer');
        }
    })
    .catch(error => {
        console.error('Error cancelling offer:', error);
        alert('An error occurred while cancelling the offer.');
    });
}

// Helper function to format numbers
function formatNumber(num) {
    return Math.floor(num).toLocaleString();
}

// Initialize market when page loads
document.addEventListener('DOMContentLoaded', () => {
    checkMarketAccess();
    
    // Set up filter change handler
    document.getElementById('offerFilter').addEventListener('change', refreshOffers);
    
    // Auto-refresh offers every 30 seconds
    setInterval(() => {
        if (marketLevel > 0) {
            refreshOffers();
            loadMyOffers();
        }
    }, 30000);
});