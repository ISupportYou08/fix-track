const locateButtonSelector = '[data-app-walk-in-locate]';

function locationErrorMessage(error) {
    if (error?.code === error?.PERMISSION_DENIED) {
        return 'Location access was not allowed. Enable it in your browser and try again.';
    }

    if (error?.code === error?.POSITION_UNAVAILABLE) {
        return 'Your current location is unavailable. Try again or enter an address manually.';
    }

    if (error?.code === error?.TIMEOUT) {
        return 'Getting your location took too long. Please try again.';
    }

    return 'We could not get your current location. Please try again or enter an address manually.';
}

function setLocationStatus(componentRoot, message) {
    const status = componentRoot?.querySelector('[data-app-walk-in-location-status]');

    if (!status) {
        return;
    }

    status.textContent = message;
    status.hidden = message === '';
}

function initializeWalkInLocationButtons(root = document) {
    root.querySelectorAll(locateButtonSelector).forEach((button) => {
        if (button.dataset.appWalkInLocateBound === 'true') {
            return;
        }

        button.dataset.appWalkInLocateBound = 'true';
        button.addEventListener('click', () => {
            const componentRoot = button.closest('[wire\\:id]');
            const componentId = componentRoot?.getAttribute('wire:id');
            const component = componentId ? window.Livewire?.find(componentId) : null;

            if (!('geolocation' in navigator)) {
                setLocationStatus(componentRoot, 'Your browser does not support current location. Enter an address manually.');

                return;
            }

            if (!component || typeof component.setWalkInShopLocation !== 'function') {
                setLocationStatus(componentRoot, 'Location is not ready yet. Please try again.');

                return;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            setLocationStatus(componentRoot, 'Finding your current location…');

            navigator.geolocation.getCurrentPosition((position) => {
                Promise.resolve(component.setWalkInShopLocation(position.coords.latitude, position.coords.longitude))
                    .then(() => setLocationStatus(componentRoot, 'My location applied. Shops are sorted nearest first.'))
                    .catch(() => setLocationStatus(componentRoot, 'Your location was found, but shops could not be updated. Please try again.'))
                    .finally(() => {
                        if (button.isConnected) {
                            button.disabled = false;
                            button.removeAttribute('aria-busy');
                        }
                    });
            }, (error) => {
                setLocationStatus(componentRoot, locationErrorMessage(error));
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }, {
                enableHighAccuracy: false,
                maximumAge: 30000,
                timeout: 10000,
            });
        });
    });
}

function scanWalkInLocationButtons() {
    initializeWalkInLocationButtons();
}

document.addEventListener('DOMContentLoaded', scanWalkInLocationButtons);
document.addEventListener('livewire:initialized', () => {
    scanWalkInLocationButtons();

    if (!window.__fixtrackWalkInLocationHook) {
        Livewire.hook('morphed', () => window.setTimeout(scanWalkInLocationButtons, 0));
        window.__fixtrackWalkInLocationHook = true;
    }
});
document.addEventListener('livewire:navigated', scanWalkInLocationButtons);

const sharingSelector = '[data-app-walk-in-tracking]';
const liveMapSelector = '[data-app-walk-in-live-map]';
const sharingStates = new Map();
const liveMaps = new WeakMap();
let walkInLeafletPromise = null;

function walkInComponent(element) {
    const componentRoot = element.closest('[wire\\:id]');
    const componentId = componentRoot?.getAttribute('wire:id');

    return componentId ? window.Livewire?.find(componentId) : null;
}

function sharingStatus(element, message) {
    const status = element.querySelector('[data-app-walk-in-sharing-status]');

    if (status) {
        status.textContent = message;
    }
}

function syncSharingControls(element) {
    const sharing = element.dataset.locationSharing === 'true';
    const active = element.dataset.walkInActive === 'true';
    const startButton = element.querySelector('[data-app-start-walk-in-sharing]');
    const declineButton = element.querySelector('[data-app-decline-walk-in-sharing]');
    const stopButton = element.querySelector('[data-app-stop-walk-in-sharing]');

    if (startButton) {
        startButton.hidden = sharing || !active;
    }

    if (declineButton) {
        declineButton.hidden = sharing || !active;
    }

    if (stopButton) {
        stopButton.hidden = !sharing || !active;
    }
}

function stopWalkInWatch(entryId) {
    const state = sharingStates.get(entryId);

    if (state?.watchId !== null && state?.watchId !== undefined && 'geolocation' in navigator) {
        navigator.geolocation.clearWatch(state.watchId);
    }

    sharingStates.delete(entryId);
}

function activeSharingElement(entryId) {
    return Array.from(document.querySelectorAll(sharingSelector)).find((element) => (
        Number(element.dataset.walkInEntryId) === entryId
        && element.dataset.walkInActive === 'true'
    ));
}

function startWalkInWatch(element) {
    const entryId = Number(element.dataset.walkInEntryId);
    const component = walkInComponent(element);

    if (!Number.isInteger(entryId) || !component?.updateWalkInLocation) {
        sharingStatus(element, 'Location sharing is not ready yet. Please try again.');

        return;
    }

    if (!('geolocation' in navigator)) {
        sharingStatus(element, 'Location sharing is unavailable because this browser does not support device location.');

        return;
    }

    stopWalkInWatch(entryId);
    sharingStatus(element, 'Requesting location permission…');

    const state = { entryId, lastSentAt: 0, watchId: null };
    state.watchId = navigator.geolocation.watchPosition((position) => {
        const currentElement = activeSharingElement(entryId);

        if (!currentElement) {
            stopWalkInWatch(entryId);

            return;
        }

        const now = Date.now();
        if (state.lastSentAt > 0 && now - state.lastSentAt < 15000) {
            return;
        }

        state.lastSentAt = now;
        const currentComponent = walkInComponent(currentElement);
        Promise.resolve(currentComponent?.updateWalkInLocation?.(
            entryId,
            position.coords.latitude,
            position.coords.longitude,
        )).then(() => {
            document.querySelectorAll(`${sharingSelector}[data-walk-in-entry-id="${entryId}"]`).forEach((trackingElement) => {
                trackingElement.dataset.locationSharing = 'true';
                sharingStatus(trackingElement, 'Location Sharing: Active · updated just now');
                syncSharingControls(trackingElement);
            });
        }).catch(() => sharingStatus(currentElement, 'Your location could not be saved. We will try again automatically.'));
    }, (error) => {
        sharingStatus(element, locationErrorMessage(error));
        stopWalkInWatch(entryId);
    }, {
        enableHighAccuracy: false,
        maximumAge: 15000,
        timeout: 12000,
    });
    sharingStates.set(entryId, state);
}

function initializeSharingElement(element) {
    if (element.dataset.appWalkInTrackingBound === 'true') {
        syncSharingControls(element);

        return;
    }

    element.dataset.appWalkInTrackingBound = 'true';
    syncSharingControls(element);
    element.querySelector('[data-app-start-walk-in-sharing]')?.addEventListener('click', () => startWalkInWatch(element));
    element.querySelector('[data-app-decline-walk-in-sharing]')?.addEventListener('click', () => {
        sharingStatus(element, 'Location Sharing: Off. You can enable it at any time while the ticket is active.');
    });
    element.querySelector('[data-app-stop-walk-in-sharing]')?.addEventListener('click', () => {
        const entryId = Number(element.dataset.walkInEntryId);
        const component = walkInComponent(element);
        stopWalkInWatch(entryId);
        Promise.resolve(component?.disableWalkInLocation?.(entryId)).then(() => {
            document.querySelectorAll(`${sharingSelector}[data-walk-in-entry-id="${entryId}"]`).forEach((trackingElement) => {
                trackingElement.dataset.locationSharing = 'false';
                sharingStatus(trackingElement, 'Location Sharing: Off');
                syncSharingControls(trackingElement);
            });
        }).catch(() => sharingStatus(element, 'Location sharing could not be stopped. Please try again.'));
    });
}

function loadWalkInLeaflet() {
    if (window.L) {
        return Promise.resolve(window.L);
    }

    if (walkInLeafletPromise) {
        return walkInLeafletPromise;
    }

    walkInLeafletPromise = new Promise((resolve, reject) => {
        if (!document.querySelector('[data-fixtrack-leaflet-style]')) {
            const style = document.createElement('link');
            style.rel = 'stylesheet';
            style.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            style.dataset.fixtrackLeafletStyle = '';
            document.head.append(style);
        }

        const existingScript = document.querySelector('[data-fixtrack-leaflet-script]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.L), { once: true });
            existingScript.addEventListener('error', reject, { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.dataset.fixtrackLeafletScript = '';
        script.addEventListener('load', () => resolve(window.L), { once: true });
        script.addEventListener('error', reject, { once: true });
        document.head.append(script);
    });

    return walkInLeafletPromise;
}

function walkInDistanceKilometers(origin, destination) {
    const radians = (degrees) => degrees * Math.PI / 180;
    const latitudeDelta = radians(destination[0] - origin[0]);
    const longitudeDelta = radians(destination[1] - origin[1]);
    const value = Math.sin(latitudeDelta / 2) ** 2
        + Math.cos(radians(origin[0])) * Math.cos(radians(destination[0])) * Math.sin(longitudeDelta / 2) ** 2;

    return 6371 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
}

async function initializeWalkInMap(element) {
    if (liveMaps.has(element)) {
        return;
    }

    const customer = [Number(element.dataset.customerLatitude), Number(element.dataset.customerLongitude)];
    const shop = [Number(element.dataset.shopLatitude), Number(element.dataset.shopLongitude)];

    if (![...customer, ...shop].every(Number.isFinite)) {
        element.textContent = 'Customer location is currently unavailable.';

        return;
    }

    try {
        const leaflet = await loadWalkInLeaflet();
        const map = leaflet.map(element, { zoomControl: true }).setView(customer, 14);
        leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);
        leaflet.marker(customer).addTo(map).bindPopup('Customer current location');
        leaflet.marker(shop).addTo(map).bindPopup(element.dataset.shopName || 'Service shop');
        const routeLine = leaflet.polyline([customer, shop], { color: '#7c3aed', weight: 4, dashArray: '8 8' }).addTo(map);
        map.fitBounds(routeLine.getBounds(), { padding: [36, 36], maxZoom: 16 });
        const distance = walkInDistanceKilometers(customer, shop);
        const badge = leaflet.control({ position: 'bottomleft' });
        badge.onAdd = () => {
            const node = leaflet.DomUtil.create('div');
            node.className = 'rounded-lg bg-white/95 px-3 py-2 text-xs font-semibold text-zinc-800 shadow dark:bg-zinc-950/95 dark:text-white';
            node.textContent = `Estimated distance: ${distance.toFixed(1)} km`;

            return node;
        };
        badge.addTo(map);
        liveMaps.set(element, map);
    } catch {
        element.textContent = 'The map is temporarily unavailable. Customer coordinates remain protected.';
        element.classList.add('flex', 'items-center', 'justify-center', 'p-6', 'text-center', 'text-sm', 'text-zinc-500');
    }
}

function scanWalkInTracking() {
    document.querySelectorAll(sharingSelector).forEach(initializeSharingElement);
    document.querySelectorAll(liveMapSelector).forEach(initializeWalkInMap);
    sharingStates.forEach((state, entryId) => {
        if (!activeSharingElement(entryId)) {
            stopWalkInWatch(entryId);
        }
    });
}

document.addEventListener('DOMContentLoaded', scanWalkInTracking);
document.addEventListener('livewire:initialized', () => {
    scanWalkInTracking();

    if (!window.__fixtrackWalkInTrackingHook) {
        Livewire.hook('morphed', () => window.setTimeout(scanWalkInTracking, 0));
        window.__fixtrackWalkInTrackingHook = true;
    }
});
document.addEventListener('livewire:navigated', scanWalkInTracking);
window.addEventListener('beforeunload', () => sharingStates.forEach((state, entryId) => stopWalkInWatch(entryId)));
