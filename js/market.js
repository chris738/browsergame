// Market functionality for the browser game

let marketLevel = 0;

// Show status message instead of alert
function showStatusMessage(message, type = 'info') {
    const existingMessage = document.getElementById('statusMessage');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const messageElement = document.createElement('div');
    messageElement.id = 'statusMessage';
    messageElement.className = `status-message status-${type}`;
    messageElement.textContent = message;
    
    // Insert at the top of the market interface
    const marketInterface = document.getElementById('marketInterface');
    if (marketInterface) {
        marketInterface.insertBefore(messageElement, marketInterface.firstChild);
    } else {
        // Fallback to body if market interface not found
        document.body.insertBefore(messageElement, document.body.firstChild);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (messageElement && messageElement.parentNode) {
            messageElement.remove();
        }
    }, 5000);
}

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
                
                // Check market ownership
                checkMarketOwnership();
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

function checkMarketOwnership() {
    // Wait a bit to ensure currentPlayerId is available
    setTimeout(() => {
        if (!window.currentPlayerId) {
            return;
        }
        
        fetch(`php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
            .then(response => response.json())
            .then(data => {
                if (data.playerInfo) {
                    const settlementOwnerId = data.playerInfo.playerId;
                    const isOwnSettlement = window.currentPlayerId === settlementOwnerId;
                    
                    updateMarketUIForOwnership(isOwnSettlement);
                }
            })
            .catch(error => console.error('Error checking market ownership:', error));
    }, 500);
}

function updateMarketUIForOwnership(isOwner) {
    const createOfferButton = document.querySelector('.create-offer-btn');
    const tradeInputs = document.querySelectorAll('.trade-form-container input, .trade-form-container select');
    
    if (!isOwner) {
        // Disable trade creation
        if (createOfferButton) {
            createOfferButton.disabled = true;
            createOfferButton.textContent = 'Create Offer (Not your settlement)';
            createOfferButton.style.opacity = '0.5';
        }
        
        tradeInputs.forEach(input => {
            input.disabled = true;
            input.style.opacity = '0.5';
        });
        
        // Add market notification
        addMarketOwnershipNotification(false);
    } else {
        // Enable trade creation
        if (createOfferButton) {
            createOfferButton.disabled = false;
            createOfferButton.textContent = 'Create Offer';
            createOfferButton.style.opacity = '1';
        }
        
        tradeInputs.forEach(input => {
            input.disabled = false;
            input.style.opacity = '1';
        });
        
        // Remove market notification
        addMarketOwnershipNotification(true);
    }
}

function addMarketOwnershipNotification(isOwner) {
    const existingNotification = document.getElementById('marketOwnershipNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    if (!isOwner) {
        const notification = document.createElement('div');
        notification.id = 'marketOwnershipNotification';
        notification.innerHTML = `
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin: 10px 0; border-radius: 4px;">
                ⚠️ <strong>Viewing another player's market</strong> - You can only create trade offers from your own settlement.
            </div>
        `;
        
        const marketInterface = document.getElementById('marketInterface');
        if (marketInterface) {
            marketInterface.insertBefore(notification, marketInterface.firstChild);
        }
    }
}

// Load all market data (offers, history, etc.)
function loadMarketData() {
    refreshOffers();
    loadMyOffers();
    loadTradeHistory();
    loadTravelingTrades();
}

// Create a new trade offer
function createTradeOffer() {
    // Check if we have a current player ID (ownership validation)
    if (!window.currentPlayerId) {
        showStatusMessage('Unable to determine current player. Please refresh the page.', 'error');
        return;
    }
    
    // Get the owner of the current settlement
    fetch(`php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
        .then(response => response.json())
        .then(ownerData => {
            const settlementOwnerId = ownerData.playerInfo ? ownerData.playerInfo.playerId : null;
            
            // Check if current player owns this settlement
            if (window.currentPlayerId !== settlementOwnerId) {
                showStatusMessage('You can only create trade offers from your own settlement. Switch to your settlement first.', 'warning');
                return;
            }
            
            // Proceed with creating the trade offer
            proceedWithTradeOffer();
        })
        .catch(error => {
            console.error('Error checking settlement ownership:', error);
            showStatusMessage('Unable to verify settlement ownership.', 'error');
        });
}

function proceedWithTradeOffer() {
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
        showStatusMessage('You must offer something!', 'warning');
        return;
    }
    
    if (totalRequested === 0) {
        showStatusMessage('You must request something in return!', 'warning');
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
            showStatusMessage('Trade offer created successfully!', 'success');
            clearOfferForm();
            loadMarketData();
        } else {
            showStatusMessage(data.message || 'Failed to create offer', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating offer:', error);
        showStatusMessage('An error occurred while creating the offer.', 'error');
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
    fetch(`php/market-backend.php?settlementId=${settlementId}&getHistory=true&limit=10`)
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

function loadTravelingTrades() {
    fetch(`php/market-backend.php?settlementId=${settlementId}&getTravelingTrades=true`)
        .then(response => response.json())
        .then(data => {
            const travelingList = document.getElementById('travelingTradesList');
            
            if (data.trades && data.trades.length > 0) {
                travelingList.innerHTML = data.trades.map(trade => createTravelingTradeHTML(trade)).join('');
            } else {
                travelingList.innerHTML = '<p>No trades currently traveling.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading traveling trades:', error);
            document.getElementById('travelingTradesList').innerHTML = '<p>Error loading traveling trades.</p>';
        });
}

function createTravelingTradeHTML(trade) {
    const isOutgoing = trade.fromSettlementId == settlementId;
    const direction = isOutgoing ? 'Outgoing' : 'Incoming';
    const otherParty = isOutgoing ? trade.toName : trade.fromName;
    const timeRemaining = trade.timeRemaining > 0 ? formatTradeTime(trade.timeRemaining) : 'Arrived';
    
    const resources = [];
    if (trade.woodAmount > 0) resources.push(`${trade.woodAmount} Wood`);
    if (trade.stoneAmount > 0) resources.push(`${trade.stoneAmount} Stone`);
    if (trade.oreAmount > 0) resources.push(`${trade.oreAmount} Ore`);
    if (trade.goldAmount > 0) resources.push(`${trade.goldAmount} Gold`);
    
    return `
        <div class="traveling-trade-item ${isOutgoing ? 'outgoing' : 'incoming'}">
            <div class="trade-header">
                <span class="trade-direction">${direction}</span>
                <span class="trade-time">${timeRemaining}</span>
            </div>
            <div class="trade-details">
                <strong>${direction === 'Outgoing' ? 'To' : 'From'}:</strong> ${otherParty || 'Unknown'}<br>
                <strong>Resources:</strong> ${resources.join(', ')}<br>
                <strong>Distance:</strong> ${trade.distance} blocks
            </div>
        </div>
    `;
}

function formatTradeTime(seconds) {
    if (seconds <= 0) return 'Arrived';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
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
        : `<div class="trade-actions-container">
            <div class="bulk-select-container">
                <input type="checkbox" id="offer_${offer.offerId}" class="offer-checkbox" onchange="updateBulkAcceptButton()">
                <label for="offer_${offer.offerId}">Select</label>
            </div>
            ${remainingTrades > 1 ? `
            <div class="quantity-container">
                <label for="quantity_${offer.offerId}">Quantity:</label>
                <input type="number" id="quantity_${offer.offerId}" min="1" max="${remainingTrades}" value="1" class="quantity-input">
            </div>` : ''}
            <button class="accept-offer-btn" onclick="acceptOffer(${offer.offerId})">Accept Trade</button>
           </div>`;

    return `
        <div class="offer-item" data-offer-id="${offer.offerId}">
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
            showStatusMessage('Trade completed successfully!', 'success');
            loadMarketData();
            // Update resources display
            fetchResources(settlementId);
        } else {
            showStatusMessage(data.message || 'Failed to complete trade', 'error');
        }
    })
    .catch(error => {
        console.error('Error accepting offer:', error);
        showStatusMessage('An error occurred while completing the trade.', 'error');
    });
}

// Cancel a trade offer
function cancelOffer(offerId) {
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
            showStatusMessage('Offer cancelled successfully!', 'success');
            loadMarketData();
        } else {
            showStatusMessage(data.message || 'Failed to cancel offer', 'error');
        }
    })
    .catch(error => {
        console.error('Error cancelling offer:', error);
        showStatusMessage('An error occurred while cancelling the offer.', 'error');
    });
}

// Update bulk accept button visibility
function updateBulkAcceptButton() {
    const checkboxes = document.querySelectorAll('.offer-checkbox:checked');
    const bulkAcceptBtn = document.getElementById('bulkAcceptBtn');
    
    if (checkboxes.length > 0) {
        bulkAcceptBtn.style.display = 'inline-block';
        bulkAcceptBtn.textContent = `Accept ${checkboxes.length} Selected Trade${checkboxes.length > 1 ? 's' : ''}`;
    } else {
        bulkAcceptBtn.style.display = 'none';
    }
}

// Bulk accept trades
function bulkAcceptTrades() {
    const checkboxes = document.querySelectorAll('.offer-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showStatusMessage('No trades selected.', 'warning');
        return;
    }
    
    const trades = [];
    checkboxes.forEach(checkbox => {
        const offerId = checkbox.id.replace('offer_', '');
        const quantityInput = document.getElementById(`quantity_${offerId}`);
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
        
        // Add multiple entries for quantity > 1
        for (let i = 0; i < quantity; i++) {
            trades.push({ offerId: parseInt(offerId) });
        }
    });
    
    showStatusMessage(`Processing ${trades.length} trade${trades.length > 1 ? 's' : ''}...`, 'info');
    
    // Process trades sequentially to avoid conflicts
    processBulkTrades(trades, 0);
}

// Process bulk trades sequentially
function processBulkTrades(trades, index) {
    if (index >= trades.length) {
        showStatusMessage(`All trades completed successfully!`, 'success');
        loadMarketData();
        fetchResources(settlementId);
        return;
    }
    
    const trade = trades[index];
    
    fetch(`php/market-backend.php?settlementId=${settlementId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'acceptOffer', offerId: trade.offerId }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Continue with next trade
            setTimeout(() => processBulkTrades(trades, index + 1), 100);
        } else {
            showStatusMessage(`Trade ${index + 1} failed: ${data.message}`, 'error');
            // Continue with remaining trades even if one fails
            setTimeout(() => processBulkTrades(trades, index + 1), 100);
        }
    })
    .catch(error => {
        console.error('Error in bulk trade:', error);
        showStatusMessage(`Trade ${index + 1} failed due to network error.`, 'error');
        // Continue with remaining trades even if one fails
        setTimeout(() => processBulkTrades(trades, index + 1), 100);
    });
}

// Helper function to format numbers
function formatNumber(num) {
    return Math.floor(num).toLocaleString();
}

// Handle trade type changes to show/hide relevant resource inputs
function handleTradeTypeChange() {
    const tradeType = document.getElementById('offerType').value;
    const offerResourcesDiv = document.querySelector('.offer-column .resource-inputs');
    const requestResourcesDiv = document.querySelector('.request-column .resource-inputs');
    
    // Reset all inputs to be visible first
    const allInputs = document.querySelectorAll('.resource-inputs label');
    allInputs.forEach(input => input.style.display = 'flex');
    
    if (tradeType === 'resource_sell') {
        // Selling resources for gold: Hide gold in offer section, hide resources in request section
        const offerGoldLabel = Array.from(offerResourcesDiv.querySelectorAll('label')).find(label => 
            label.querySelector('#offerGold'));
        const requestResourceLabels = Array.from(requestResourcesDiv.querySelectorAll('label')).filter(label => 
            label.querySelector('#requestWood') || label.querySelector('#requestStone') || label.querySelector('#requestOre'));
        
        if (offerGoldLabel) offerGoldLabel.style.display = 'none';
        requestResourceLabels.forEach(label => label.style.display = 'none');
        
        // Update column headers
        document.querySelector('.offer-column h4').textContent = 'Resources you sell:';
        document.querySelector('.request-column h4').textContent = 'Gold you want:';
        
    } else if (tradeType === 'resource_buy') {
        // Buying resources with gold: Hide resources in offer section, hide gold in request section
        const offerResourceLabels = Array.from(offerResourcesDiv.querySelectorAll('label')).filter(label => 
            label.querySelector('#offerWood') || label.querySelector('#offerStone') || label.querySelector('#offerOre'));
        const requestGoldLabel = Array.from(requestResourcesDiv.querySelectorAll('label')).find(label => 
            label.querySelector('#requestGold'));
        
        offerResourceLabels.forEach(label => label.style.display = 'none');
        if (requestGoldLabel) requestGoldLabel.style.display = 'none';
        
        // Update column headers
        document.querySelector('.offer-column h4').textContent = 'Gold you offer:';
        document.querySelector('.request-column h4').textContent = 'Resources you buy:';
        
    } else {
        // Resource trade: Hide gold inputs from both sections
        const offerGoldLabel = Array.from(offerResourcesDiv.querySelectorAll('label')).find(label => 
            label.querySelector('#offerGold'));
        const requestGoldLabel = Array.from(requestResourcesDiv.querySelectorAll('label')).find(label => 
            label.querySelector('#requestGold'));
            
        if (offerGoldLabel) offerGoldLabel.style.display = 'none';
        if (requestGoldLabel) requestGoldLabel.style.display = 'none';
        
        // Update column headers
        document.querySelector('.offer-column h4').textContent = 'What you offer:';
        document.querySelector('.request-column h4').textContent = 'What you want in return:';
    }
}

// Initialize market when page loads
document.addEventListener('DOMContentLoaded', () => {
    checkMarketAccess();
    
    // Set up filter change handler
    document.getElementById('offerFilter').addEventListener('change', refreshOffers);
    
    // Set up trade type change handler
    document.getElementById('offerType').addEventListener('change', handleTradeTypeChange);
    
    // Initialize trade type interface
    handleTradeTypeChange();
    
    // Auto-refresh offers every 30 seconds
    setInterval(() => {
        if (marketLevel > 0) {
            refreshOffers();
            loadMyOffers();
            loadTravelingTrades();
        }
    }, 30000);
});