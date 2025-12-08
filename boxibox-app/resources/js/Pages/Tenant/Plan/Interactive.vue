<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    boxes: Array,
    elements: Array, // Éléments du plan depuis l'éditeur
    configuration: Object,
    statistics: Object,
});

const selectedSite = ref(props.currentSite?.id);
const showMenu = ref(false);

// Popup state
const showPopup = ref(false);
const popupBox = ref(null);
const popupPosition = ref({ x: 0, y: 0 });

// Modal state for click
const showModal = ref(false);
const modalBox = ref(null);

// Status colors - Exact Buxida colors
const statusColors = {
    available: '#4CAF50',
    occupied: '#2196F3',
    reserved: '#FF9800',
    ending: '#FFEB3B',
    litigation: '#9C27B0',
    maintenance: '#f44336',
    unavailable: '#9E9E9E',
};

const statusLabels = {
    available: 'Libre',
    occupied: 'Occupé',
    reserved: 'Réservé',
    ending: 'Fin de contrat',
    litigation: 'Litige',
    maintenance: 'Maintenance',
    unavailable: 'Indisponible',
};

// Menu items
const menuItems = [
    { name: 'Dashboard', icon: 'fas fa-home', route: 'tenant.dashboard' },
    { name: 'Boxes', icon: 'fas fa-box', route: 'tenant.boxes.index' },
    { name: 'Contrats', icon: 'fas fa-file-contract', route: 'tenant.contracts.index' },
    { name: 'Clients', icon: 'fas fa-users', route: 'tenant.customers.index' },
    { name: 'Factures', icon: 'fas fa-file-invoice-dollar', route: 'tenant.invoices.index' },
    { name: 'Paiements', icon: 'fas fa-credit-card', route: 'tenant.payments.index' },
];

// Toggle menu
const toggleMenu = () => {
    showMenu.value = !showMenu.value;
};

// Change site
const changeSite = () => {
    router.get(route('tenant.plan.interactive'), { site_id: selectedSite.value });
};

// Utiliser les éléments sauvegardés depuis l'éditeur, sinon générer un layout de démonstration
const boxLayout = computed(() => {
    // Si on a des éléments sauvegardés depuis l'éditeur, les utiliser
    if (props.elements && props.elements.length > 0) {
        return props.elements.map(el => ({
            id: el.id,
            name: el.name || el.label || el.box?.number || '',
            x: el.x,
            y: el.y,
            w: el.w || el.width || 35,
            h: el.h || el.height || 30,
            vol: el.vol || el.volume || el.box?.volume || 0,
            status: el.status || 'available',
            fill: el.fill,
            type: el.type || 'box',
            isLift: el.type === 'lift',
            boxId: el.boxId || el.box?.id,
            contract: el.contract ? {
                id: el.contract.id,
                contract_number: el.contract.contract_number,
                customer: el.contract.customer ? {
                    id: el.contract.customer.id,
                    name: el.contract.customer.name,
                } : null,
            } : null,
        }));
    }
    // Sinon, générer un layout de démonstration
    return generateExactBuxidaLayout();
});

function generateExactBuxidaLayout() {
    const boxes = [];
    const statuses = ['available', 'occupied', 'occupied', 'occupied', 'occupied', 'reserved', 'ending'];
    let id = 0;

    const getStatus = () => statuses[Math.floor(Math.random() * statuses.length)];
    const getContract = (status) => status === 'occupied' ? {
        id: Math.floor(Math.random() * 1000) + 1,
        contract_number: `CO${2024}${String(Math.floor(Math.random() * 100000)).padStart(5, '0')}`,
        start_date: `2024-${String(Math.floor(Math.random() * 12) + 1).padStart(2, '0')}-${String(Math.floor(Math.random() * 28) + 1).padStart(2, '0')}`,
        customer: {
            id: Math.floor(Math.random() * 500) + 1,
            name: ['Martin Dupont', 'Marie Dubois', 'Jean Lefebvre', 'Sophie Bernard', 'Amba Akatshi Philomène'][Math.floor(Math.random() * 5)]
        }
    } : null;

    // ============ LEFT SIDE BOXES ============
    boxes.push({ id: id++, name: 'V6', x: 15, y: 120, w: 35, h: 50, vol: 18, status: getStatus() });
    boxes.push({ id: id++, name: 'L8', x: 15, y: 280, w: 35, h: 40, vol: 11, status: getStatus() });
    boxes.push({ id: id++, name: 'P3', x: 15, y: 520, w: 30, h: 25, vol: 1.5, status: getStatus() });

    // ============ M COLUMN ============
    const mBoxes = [
        { name: 'M14', x: 55, y: 95, w: 40, h: 35, vol: 18 },
        { name: 'M12', x: 55, y: 132, w: 40, h: 35, vol: 18 },
        { name: 'M10', x: 55, y: 169, w: 40, h: 35, vol: 18 },
        { name: 'M8', x: 55, y: 206, w: 40, h: 35, vol: 18 },
        { name: 'M6', x: 55, y: 243, w: 40, h: 35, vol: 18 },
        { name: 'M4', x: 55, y: 280, w: 40, h: 35, vol: 18 },
        { name: 'M2', x: 55, y: 317, w: 35, h: 30, vol: 18 },
    ];
    mBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ K COLUMN ============
    const kBoxes = [
        { name: 'K12', x: 100, y: 95, w: 30, h: 28, vol: 9 },
        { name: 'K10', x: 100, y: 125, w: 30, h: 28, vol: 9 },
        { name: 'K8', x: 100, y: 155, w: 30, h: 28, vol: 9 },
        { name: 'K6', x: 100, y: 185, w: 30, h: 28, vol: 9 },
        { name: 'K4', x: 100, y: 215, w: 30, h: 28, vol: 9 },
        { name: 'K2', x: 100, y: 245, w: 30, h: 28, vol: 9 },
        { name: 'K11', x: 132, y: 95, w: 28, h: 28, vol: 9 },
        { name: 'K9', x: 132, y: 125, w: 28, h: 28, vol: 9 },
        { name: 'K7', x: 132, y: 155, w: 28, h: 28, vol: 9 },
        { name: 'K5', x: 132, y: 185, w: 28, h: 28, vol: 16 },
        { name: 'K3', x: 132, y: 215, w: 28, h: 28, vol: 9 },
        { name: 'K1', x: 132, y: 245, w: 28, h: 28, vol: 18 },
    ];
    kBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ J COLUMN ============
    const jBoxes = [
        { name: 'J14', x: 165, y: 95, w: 28, h: 28, vol: 18 },
        { name: 'J12', x: 165, y: 125, w: 28, h: 28, vol: 18 },
        { name: 'J13', x: 195, y: 95, w: 45, h: 35, vol: 30 },
        { name: 'I16', x: 242, y: 95, w: 45, h: 35, vol: 30 },
        { name: 'I14', x: 195, y: 145, w: 50, h: 55, vol: 35 },
        { name: 'J11', x: 195, y: 202, w: 25, h: 25, vol: 9 },
        { name: 'J9', x: 195, y: 229, w: 25, h: 25, vol: 9 },
        { name: 'J7', x: 195, y: 256, w: 25, h: 25, vol: 9 },
        { name: 'J5', x: 195, y: 283, w: 25, h: 25, vol: 9 },
        { name: 'J3', x: 195, y: 310, w: 25, h: 25, vol: 9 },
        { name: 'J1', x: 195, y: 337, w: 25, h: 25, vol: 9 },
        { name: 'I12', x: 222, y: 202, w: 25, h: 25, vol: 9 },
        { name: 'I10', x: 222, y: 229, w: 25, h: 25, vol: 9 },
        { name: 'I8', x: 222, y: 256, w: 25, h: 25, vol: 9 },
        { name: 'I6', x: 165, y: 155, w: 28, h: 28, vol: 16 },
        { name: 'J8', x: 165, y: 185, w: 28, h: 28, vol: 16 },
        { name: 'J6', x: 165, y: 215, w: 28, h: 28, vol: 9 },
        { name: 'J4', x: 165, y: 283, w: 28, h: 25, vol: 9 },
        { name: 'J2', x: 165, y: 310, w: 28, h: 25, vol: 18 },
    ];
    jBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ I COLUMN ============
    const iBoxes = [
        { name: 'I21', x: 290, y: 95, w: 28, h: 28, vol: 9 },
        { name: 'I19', x: 290, y: 125, w: 28, h: 28, vol: 9 },
        { name: 'I17', x: 290, y: 155, w: 28, h: 28, vol: 9 },
        { name: 'I15', x: 290, y: 185, w: 28, h: 28, vol: 9 },
        { name: 'I13', x: 290, y: 215, w: 28, h: 28, vol: 9 },
        { name: 'I11', x: 290, y: 245, w: 28, h: 28, vol: 9 },
        { name: 'I9', x: 290, y: 275, w: 28, h: 28, vol: 9 },
        { name: 'I7', x: 290, y: 305, w: 28, h: 28, vol: 9 },
        { name: 'I5', x: 290, y: 335, w: 28, h: 28, vol: 9 },
        { name: 'I3', x: 290, y: 365, w: 28, h: 28, vol: 9 },
        { name: 'I1', x: 290, y: 395, w: 28, h: 28, vol: 9 },
    ];
    iBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ H COLUMN ============
    const hBoxes = [
        { name: 'H22', x: 322, y: 95, w: 28, h: 28, vol: 9 },
        { name: 'H20', x: 322, y: 125, w: 28, h: 28, vol: 9 },
        { name: 'H18', x: 322, y: 155, w: 28, h: 28, vol: 9 },
        { name: 'H16', x: 322, y: 185, w: 28, h: 28, vol: 9 },
        { name: 'H14', x: 322, y: 215, w: 28, h: 28, vol: 9 },
        { name: 'H12', x: 322, y: 245, w: 28, h: 28, vol: 9 },
        { name: 'H10', x: 322, y: 275, w: 28, h: 28, vol: 9 },
        { name: 'H8', x: 322, y: 305, w: 28, h: 28, vol: 9 },
        { name: 'H6', x: 322, y: 335, w: 28, h: 28, vol: 9 },
        { name: 'H4', x: 322, y: 365, w: 28, h: 28, vol: 9 },
        { name: 'H2', x: 322, y: 395, w: 28, h: 28, vol: 9 },
        { name: 'H19', x: 355, y: 95, w: 40, h: 45, vol: 16 },
        { name: 'H15', x: 355, y: 145, w: 40, h: 45, vol: 16 },
        { name: 'H13', x: 355, y: 195, w: 40, h: 45, vol: 16 },
        { name: 'H9', x: 355, y: 270, w: 40, h: 40, vol: 16 },
        { name: 'H7', x: 355, y: 315, w: 40, h: 35, vol: 16 },
        { name: 'H3', x: 355, y: 355, w: 40, h: 35, vol: 16 },
    ];
    hBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ G COLUMN ============
    const gBoxes = [
        { name: 'G21', x: 400, y: 95, w: 35, h: 32, vol: 18 },
        { name: 'G19', x: 400, y: 130, w: 35, h: 32, vol: 18 },
        { name: 'G17', x: 400, y: 165, w: 35, h: 32, vol: 18 },
        { name: 'G16', x: 400, y: 200, w: 35, h: 32, vol: 18 },
        { name: 'G10', x: 400, y: 270, w: 40, h: 38, vol: 16 },
        { name: 'G8', x: 400, y: 312, w: 40, h: 35, vol: 16 },
        { name: 'G9', x: 442, y: 270, w: 35, h: 32, vol: 18 },
        { name: 'G7', x: 442, y: 305, w: 35, h: 32, vol: 18 },
        { name: 'G5', x: 442, y: 340, w: 35, h: 32, vol: 18 },
        { name: 'G3', x: 442, y: 375, w: 35, h: 32, vol: 18 },
        { name: 'G1', x: 442, y: 410, w: 35, h: 32, vol: 18 },
        { name: 'G2', x: 400, y: 410, w: 40, h: 35, vol: 18 },
    ];
    gBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ F COLUMN ============
    const fBoxes = [
        { name: 'F14', x: 485, y: 95, w: 42, h: 35, vol: 18 },
        { name: 'F12', x: 485, y: 133, w: 42, h: 35, vol: 18 },
        { name: 'F10', x: 485, y: 171, w: 42, h: 35, vol: 18 },
        { name: 'F8', x: 485, y: 209, w: 42, h: 35, vol: 18 },
        { name: 'F6', x: 485, y: 247, w: 42, h: 35, vol: 18 },
        { name: 'F5', x: 485, y: 285, w: 42, h: 35, vol: 18 },
        { name: 'F3', x: 485, y: 323, w: 42, h: 35, vol: 18 },
        { name: 'F1', x: 485, y: 361, w: 42, h: 35, vol: 18 },
        { name: 'F13', x: 530, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'F11', x: 530, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'F9', x: 530, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'F7', x: 530, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'F2', x: 485, y: 399, w: 42, h: 35, vol: 18 },
    ];
    fBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ E COLUMN ============
    const eBoxes = [
        { name: 'E14', x: 572, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'E12', x: 572, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'E10', x: 572, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'E8', x: 572, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'E6', x: 572, y: 247, w: 38, h: 35, vol: 18 },
        { name: 'E4', x: 572, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'E2', x: 572, y: 361, w: 38, h: 35, vol: 18 },
        { name: 'E13', x: 612, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'E11', x: 612, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'E9', x: 612, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'E7', x: 612, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'E5', x: 612, y: 247, w: 38, h: 35, vol: 18 },
        { name: 'E3', x: 612, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'E1', x: 612, y: 361, w: 38, h: 35, vol: 18 },
    ];
    eBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ D COLUMN ============
    const dBoxes = [
        { name: 'D14', x: 655, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'D12', x: 655, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'D10', x: 655, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'D8', x: 655, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'D7', x: 655, y: 247, w: 38, h: 35, vol: 18 },
        { name: 'D4', x: 655, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'D2', x: 655, y: 361, w: 38, h: 35, vol: 18 },
        { name: 'D13', x: 695, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'D11', x: 695, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'D9', x: 695, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'C8', x: 695, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'D3', x: 695, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'D1', x: 695, y: 361, w: 38, h: 35, vol: 18 },
    ];
    dBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ C COLUMN ============
    const cBoxes = [
        { name: 'C14', x: 738, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'C12', x: 738, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'C10', x: 738, y: 171, w: 38, h: 35, vol: 18 },
        { name: 'C9', x: 738, y: 209, w: 38, h: 35, vol: 18 },
        { name: 'C7', x: 738, y: 247, w: 38, h: 35, vol: 18 },
        { name: 'C5', x: 738, y: 285, w: 38, h: 35, vol: 18 },
        { name: 'C3', x: 738, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'C1', x: 738, y: 361, w: 38, h: 35, vol: 18 },
        { name: 'C13', x: 778, y: 95, w: 38, h: 35, vol: 18 },
        { name: 'C11', x: 778, y: 133, w: 38, h: 35, vol: 18 },
        { name: 'C4', x: 778, y: 323, w: 38, h: 35, vol: 18 },
        { name: 'C2', x: 778, y: 361, w: 38, h: 35, vol: 18 },
    ];
    cBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ X COLUMN ============
    const xBoxes = [
        { name: 'X9', x: 820, y: 95, w: 35, h: 35, vol: 25 },
        { name: 'X7', x: 820, y: 145, w: 35, h: 35, vol: 25 },
        { name: 'X11', x: 858, y: 95, w: 40, h: 40, vol: 25 },
        { name: 'X13', x: 858, y: 140, w: 40, h: 40, vol: 30 },
        { name: 'X14', x: 820, y: 183, w: 35, h: 30, vol: 25 },
        { name: 'X12', x: 820, y: 215, w: 35, h: 30, vol: 25 },
        { name: 'X10', x: 820, y: 247, w: 35, h: 28, vol: 18 },
        { name: 'X8', x: 820, y: 277, w: 35, h: 28, vol: 18 },
        { name: 'X6', x: 820, y: 307, w: 35, h: 28, vol: 18 },
        { name: 'X4', x: 820, y: 337, w: 35, h: 28, vol: 18 },
        { name: 'X2', x: 820, y: 367, w: 35, h: 28, vol: 18 },
        { name: 'R1', x: 858, y: 185, w: 22, h: 22, vol: 11 },
        { name: 'R2', x: 882, y: 185, w: 22, h: 22, vol: 12 },
        { name: 'R3', x: 858, y: 209, w: 22, h: 22, vol: 11 },
        { name: 'R4', x: 882, y: 209, w: 22, h: 22, vol: 12 },
        { name: 'R5', x: 870, y: 235, w: 30, h: 40, vol: 20 },
        { name: 'X5', x: 858, y: 280, w: 40, h: 35, vol: 18 },
        { name: 'X3', x: 858, y: 320, w: 55, h: 50, vol: 63 },
    ];
    xBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // ============ BOTTOM SECTION ============
    const lBoxes = [
        { name: 'L21', x: 55, y: 460, w: 45, h: 40, vol: 25 },
        { name: 'L6', x: 165, y: 460, w: 42, h: 40, vol: 18 },
        { name: 'L4', x: 210, y: 460, w: 42, h: 40, vol: 25 },
        { name: 'L19', x: 55, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L17', x: 92, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L15', x: 129, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L13', x: 166, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L11', x: 203, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L9', x: 240, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L7', x: 277, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L5', x: 314, y: 520, w: 35, h: 35, vol: 18 },
        { name: 'L3', x: 351, y: 520, w: 35, h: 35, vol: 19 },
    ];
    lBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // LIFTs
    boxes.push({ id: id++, name: 'LIFT', x: 105, y: 460, w: 55, h: 40, vol: 0, status: 'unavailable', isLift: true });
    boxes.push({ id: id++, name: 'LIFT', x: 775, y: 448, w: 55, h: 40, vol: 0, status: 'unavailable', isLift: true });

    // B & A Rows
    for (let i = 0; i < 20; i++) {
        const s = getStatus();
        boxes.push({ id: id++, name: `B${42 - i * 2}`, x: 395 + i * 22, y: 448, w: 20, h: 18, vol: 3, status: s, contract: getContract(s) });
    }
    for (let i = 0; i < 20; i++) {
        const s = getStatus();
        boxes.push({ id: id++, name: `A${43 - i}`, x: 395 + i * 22, y: 520, w: 20, h: 18, vol: 3, status: s, contract: getContract(s) });
    }

    // N Row
    const nBoxes = [
        { name: 'N1', x: 720, y: 495, w: 55, h: 50, vol: 30 },
        { name: 'N2', x: 778, y: 495, w: 55, h: 50, vol: 50 },
        { name: 'N3', x: 836, y: 495, w: 55, h: 50, vol: 50 },
    ];
    nBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // Q Row
    const qBoxes = [
        { name: 'X1', x: 870, y: 380, w: 28, h: 25, vol: 18 },
        { name: 'Q1', x: 870, y: 410, w: 28, h: 22, vol: 30 },
        { name: 'Q3', x: 870, y: 434, w: 28, h: 22, vol: 30 },
        { name: 'Q5', x: 870, y: 458, w: 28, h: 22, vol: 18 },
        { name: 'Q2', x: 900, y: 380, w: 28, h: 22, vol: 30 },
        { name: 'Q4', x: 900, y: 404, w: 28, h: 22, vol: 30 },
        { name: 'Q6', x: 900, y: 428, w: 28, h: 22, vol: 18 },
        { name: 'Q8', x: 900, y: 452, w: 35, h: 30, vol: 32 },
    ];
    qBoxes.forEach(b => { const s = getStatus(); boxes.push({ id: id++, ...b, status: s, contract: getContract(s) }); });

    // O1
    boxes.push({ id: id++, name: 'O1', x: 910, y: 95, w: 25, h: 30, vol: 18, status: getStatus() });

    return boxes;
}

// Popup handlers
const handleBoxMouseEnter = (box, event) => {
    if (box.isLift) return;
    popupBox.value = box;
    updatePopupPosition(event);
    showPopup.value = true;
};

const handleBoxMouseMove = (event) => {
    updatePopupPosition(event);
};

const handleBoxMouseLeave = () => {
    showPopup.value = false;
    popupBox.value = null;
};

const updatePopupPosition = (event) => {
    const svg = document.getElementById('plan-svg');
    if (!svg) return;
    const rect = svg.getBoundingClientRect();
    let x = event.clientX - rect.left + 15;
    let y = event.clientY - rect.top + 15;
    if (x + 320 > rect.width) x = event.clientX - rect.left - 320;
    if (y + 180 > rect.height) y = event.clientY - rect.top - 180;
    popupPosition.value = { x: Math.max(0, x), y: Math.max(0, y) };
};

// Click handler
const handleBoxClick = (box) => {
    if (box.isLift) return;
    modalBox.value = box;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    modalBox.value = null;
};

// Navigate to customer
const goToCustomer = (customerId) => {
    if (customerId) {
        router.visit(route('tenant.customers.show', customerId));
    }
};

// Navigate to contract
const goToContract = (contractId) => {
    if (contractId) {
        router.visit(route('tenant.contracts.show', contractId));
    }
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
};

// Get text color
const getTextColor = (status) => status === 'ending' ? '#333' : '#fff';

// Stats
const totalBoxes = computed(() => boxLayout.value.filter(b => !b.isLift).length);
const occupiedBoxes = computed(() => boxLayout.value.filter(b => b.status === 'occupied').length);
const freeBoxes = computed(() => boxLayout.value.filter(b => b.status === 'available').length);
</script>

<template>
    <div class="plan-fullpage">
        <!-- Sidebar Menu -->
        <div class="sidebar" :class="{ open: showMenu }">
            <div class="sidebar-header">
                <span class="logo">BoxiBox</span>
                <button @click="toggleMenu" class="close-menu">&times;</button>
            </div>
            <nav class="sidebar-nav">
                <Link v-for="item in menuItems" :key="item.route" :href="route(item.route)" class="nav-item">
                    <i :class="item.icon"></i>
                    <span>{{ item.name }}</span>
                </Link>
                <Link :href="route('tenant.plan.editor')" class="nav-item editor-link">
                    <i class="fas fa-pencil-ruler"></i>
                    <span>Éditeur de Plan</span>
                </Link>
            </nav>
        </div>

        <!-- Overlay -->
        <div v-if="showMenu" class="overlay" @click="toggleMenu"></div>

        <!-- Header -->
        <div class="plan-header">
            <div class="header-left">
                <button @click="toggleMenu" class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Plans <span class="subtitle">État des boxes</span></h1>
            </div>
            <div class="header-center">
                <i class="fas fa-street-view"></i>
                <span class="stats">
                    PLAN - NB BOX : <strong>{{ totalBoxes }}</strong> -
                    OCCUPÉ : <strong>{{ occupiedBoxes }}</strong> -
                    LIBRE : <strong>{{ freeBoxes }}</strong>
                </span>
            </div>
            <div class="header-right">
                <select v-model="selectedSite" @change="changeSite" class="site-select">
                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                </select>
                <Link :href="route('tenant.plan.editor')" class="editor-btn">
                    <i class="fas fa-pencil-ruler"></i> Éditeur
                </Link>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend-bar">
            <div class="legend-sizes">
                <div class="size-item"><div class="size-box" style="width:25px;height:18px;">3,5</div><span>3,5m³</span></div>
                <div class="size-item"><div class="size-box" style="width:40px;height:30px;">75</div><span>75m³</span></div>
                <div class="size-item"><div class="size-box" style="width:45px;height:35px;">100</div><span>100m³</span></div>
                <div class="size-item"><div class="size-box" style="width:32px;height:25px;">16</div><span>16m³</span></div>
                <div class="size-item"><div class="size-box" style="width:35px;height:28px;">18</div><span>18m³</span></div>
                <div class="size-item"><div class="size-box" style="width:50px;height:38px;">110</div><span>110m³</span></div>
            </div>
            <div class="legend-status">
                <i class="fas fa-user person-icon"></i>
                <div class="status-item"><div class="status-box libre"></div><span>Libre</span></div>
                <div class="status-item"><div class="status-box occupe"></div><span>Occupé</span></div>
                <div class="status-item"><div class="status-box reserve"></div><span>Réservé</span></div>
                <div class="status-item"><div class="status-box dedite"></div><span>Fin contrat</span></div>
                <div class="status-item"><div class="status-box litige"></div><span>Litige</span></div>
                <div class="status-item"><div class="status-box maintenance"></div><span>Maintenance</span></div>
            </div>
        </div>

        <!-- SVG Plan -->
        <div class="plan-wrapper">
            <svg id="plan-svg" viewBox="0 0 950 580" preserveAspectRatio="xMidYMid meet">
                <rect x="50" y="85" width="870" height="480" fill="none" stroke="#333" stroke-width="2" rx="3"/>
                <g v-for="box in boxLayout" :key="box.id" class="box-group"
                   @mouseenter="handleBoxMouseEnter(box, $event)"
                   @mousemove="handleBoxMouseMove"
                   @mouseleave="handleBoxMouseLeave"
                   @click="handleBoxClick(box)">
                    <rect :x="box.x" :y="box.y" :width="box.w" :height="box.h"
                        :fill="box.isLift ? '#fff' : statusColors[box.status]"
                        stroke="#333" stroke-width="1" rx="1" class="box-rect"/>
                    <text :x="box.x + box.w/2" :y="box.y + box.h/2 - (box.h > 25 ? 4 : 0)"
                        :fill="box.isLift ? '#333' : getTextColor(box.status)" class="box-name">
                        {{ box.name }}
                    </text>
                    <text v-if="box.vol > 0 && box.h > 25" :x="box.x + box.w/2" :y="box.y + box.h/2 + 8"
                        :fill="getTextColor(box.status)" class="box-vol">
                        {{ box.vol }}m³
                    </text>
                </g>
            </svg>

            <!-- Popup with clickable client -->
            <div v-if="showPopup && popupBox" class="popup" :style="{left: popupPosition.x + 'px', top: popupPosition.y + 'px'}">
                <div class="popup-header">Box {{ popupBox.name }}</div>
                <div class="popup-body">
                    <div><strong>Type : </strong>{{ popupBox.vol }}m³ (0 m² - {{ popupBox.vol }} m³)</div>
                    <div><strong>État : </strong>{{ statusLabels[popupBox.status] }}
                        <template v-if="popupBox.contract"> - Contrat : {{ popupBox.contract.contract_number }}</template>
                    </div>
                    <div v-if="popupBox.contract">
                        <strong>Contrat : </strong>
                        <a href="#" @click.prevent="goToContract(popupBox.contract.id)" class="popup-link">
                            {{ popupBox.contract.contract_number }}
                        </a>
                    </div>
                    <div v-if="popupBox.contract?.customer">
                        <strong>Client : </strong>
                        <a href="#" @click.prevent="goToCustomer(popupBox.contract.customer.id)" class="popup-link client-link">
                            {{ popupBox.contract.customer.name }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal && modalBox" class="modal-overlay" @click.self="closeModal">
            <div class="modal-content">
                <div class="modal-header" :style="{background: statusColors[modalBox.status]}">
                    <h2>Box {{ modalBox.name }}</h2>
                    <button @click="closeModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="info-row">
                        <span class="label">État</span>
                        <span class="value status-badge" :style="{background: statusColors[modalBox.status], color: getTextColor(modalBox.status)}">
                            {{ statusLabels[modalBox.status] }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Volume</span>
                        <span class="value">{{ modalBox.vol }} m³</span>
                    </div>
                    <template v-if="modalBox.contract">
                        <div class="info-row clickable" @click="goToContract(modalBox.contract.id)">
                            <span class="label">Contrat</span>
                            <span class="value link-value">{{ modalBox.contract.contract_number }} <i class="fas fa-external-link-alt"></i></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Date début</span>
                            <span class="value">{{ formatDate(modalBox.contract.start_date) }}</span>
                        </div>
                        <div class="info-row clickable" @click="goToCustomer(modalBox.contract.customer?.id)">
                            <span class="label">Client</span>
                            <span class="value link-value">{{ modalBox.contract.customer?.name }} <i class="fas fa-external-link-alt"></i></span>
                        </div>
                    </template>
                    <div v-else class="available-box">
                        <div class="available-icon">✓</div>
                        <p>Ce box est disponible</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <template v-if="modalBox.status === 'available'">
                        <Link :href="route('tenant.contracts.create', { box_id: modalBox.boxId })" class="btn btn-success">
                            <i class="fas fa-plus"></i> Créer un contrat
                        </Link>
                    </template>
                    <template v-else-if="modalBox.contract">
                        <button @click="goToContract(modalBox.contract.id)" class="btn btn-primary">
                            <i class="fas fa-file-contract"></i> Voir le contrat
                        </button>
                        <button @click="goToCustomer(modalBox.contract.customer?.id)" class="btn btn-info">
                            <i class="fas fa-user"></i> Voir le client
                        </button>
                    </template>
                    <button @click="closeModal" class="btn btn-secondary">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.plan-fullpage {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #f5f5f5;
    display: flex;
    flex-direction: column;
    z-index: 9999;
    font-family: 'Open Sans', sans-serif;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0; left: -280px;
    width: 280px; height: 100%;
    background: #2c3e50;
    z-index: 10001;
    transition: left 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.3);
}
.sidebar.open { left: 0; }
.sidebar-header {
    padding: 20px;
    background: #1a252f;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sidebar-header .logo {
    color: #3498db;
    font-size: 24px;
    font-weight: 700;
}
.close-menu {
    background: none; border: none;
    color: #fff; font-size: 28px;
    cursor: pointer;
}
.sidebar-nav { padding: 20px 0; }
.nav-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 25px;
    color: #ecf0f1;
    text-decoration: none;
    transition: background 0.2s;
}
.nav-item:hover { background: #34495e; }
.nav-item i { width: 20px; text-align: center; }
.editor-link {
    background: linear-gradient(135deg, #3498db, #9b59b6);
    margin: 20px;
    border-radius: 8px;
}
.editor-link:hover { opacity: 0.9; }

.overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
}

/* Header */
.plan-header {
    background: #fff;
    border-bottom: 1px solid #ddd;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}
.menu-btn {
    background: #3498db;
    border: none;
    color: #fff;
    width: 40px; height: 40px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
}
.menu-btn:hover { background: #2980b9; }
.plan-header h1 {
    font-size: 22px;
    margin: 0;
    color: #333;
    font-weight: 400;
}
.plan-header .subtitle {
    font-size: 14px;
    color: #888;
    margin-left: 10px;
}
.header-center {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #32c5d2;
    font-weight: 600;
}
.header-center i { font-size: 20px; }
.header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.site-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.editor-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #3498db, #9b59b6);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}
.editor-btn:hover { opacity: 0.9; }

/* Legend */
.legend-bar {
    background: #fff;
    border-bottom: 1px solid #ddd;
    padding: 8px 20px;
    display: flex;
    gap: 30px;
    align-items: center;
}
.legend-sizes, .legend-status {
    display: flex;
    gap: 12px;
    align-items: center;
}
.size-item, .status-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 10px;
    color: #666;
}
.size-box {
    background: #4CAF50;
    border: 1px solid #333;
    color: #fff;
    font-size: 8px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
}
.status-box {
    width: 30px; height: 22px;
    border: 1px solid #333;
    margin-bottom: 2px;
}
.status-box.libre { background: #4CAF50; }
.status-box.occupe { background: #2196F3; }
.status-box.reserve { background: #FF9800; }
.status-box.dedite { background: #FFEB3B; }
.status-box.litige { background: #9C27B0; }
.status-box.maintenance { background: #f44336; }
.person-icon {
    font-size: 22px;
    color: #2196F3;
    margin-right: 10px;
}

/* Plan */
.plan-wrapper {
    flex: 1;
    position: relative;
    overflow: auto;
    background: #fff;
    margin: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
#plan-svg {
    width: 100%;
    height: 100%;
    min-height: 550px;
}
.box-group { cursor: pointer; }
.box-rect { transition: all 0.15s; }
.box-rect:hover {
    stroke-width: 2px;
    filter: brightness(1.1);
}
.box-name {
    font-size: 9px;
    font-weight: bold;
    text-anchor: middle;
    dominant-baseline: middle;
    pointer-events: none;
}
.box-vol {
    font-size: 7px;
    text-anchor: middle;
    pointer-events: none;
}

/* Popup */
.popup {
    position: absolute;
    z-index: 100;
    width: 320px;
    background: #fff;
    border: 1px solid #333;
    box-shadow: 0 3px 15px rgba(0,0,0,0.2);
}
.popup-header {
    background: #3598dc;
    color: #fff;
    padding: 10px 15px;
    font-size: 16px;
    font-weight: 600;
}
.popup-body {
    padding: 12px 15px;
    font-size: 13px;
    line-height: 1.8;
}
.popup-body strong { color: #333; }
.popup-link {
    color: #3498db;
    text-decoration: none;
    cursor: pointer;
}
.popup-link:hover {
    text-decoration: underline;
    color: #2980b9;
}
.client-link {
    color: #27ae60;
    font-weight: 600;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10002;
}
.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 480px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    overflow: hidden;
}
.modal-header {
    padding: 18px 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
    font-size: 22px;
}
.close-btn {
    background: none; border: none;
    color: #fff; font-size: 30px;
    cursor: pointer; line-height: 1;
}
.modal-body { padding: 24px; }
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.info-row.clickable {
    cursor: pointer;
    transition: background 0.2s;
}
.info-row.clickable:hover {
    background: #f8f9fa;
}
.info-row .label {
    color: #666;
    font-size: 14px;
}
.info-row .value {
    font-weight: 600;
    color: #333;
}
.link-value {
    color: #3498db !important;
}
.link-value i {
    font-size: 12px;
    margin-left: 5px;
}
.status-badge {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 13px;
}
.available-box {
    text-align: center;
    padding: 30px;
    background: #e8f5e9;
    border-radius: 12px;
    margin-top: 15px;
}
.available-icon {
    width: 60px; height: 60px;
    background: #4CAF50;
    color: #fff;
    border-radius: 50%;
    font-size: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}
.available-box p {
    color: #2e7d32;
    font-weight: 600;
    margin: 0;
    font-size: 16px;
}
.modal-footer {
    padding: 18px 24px;
    background: #f8f9fa;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}
.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    transition: all 0.2s;
}
.btn-success { background: #4CAF50; color: #fff; }
.btn-primary { background: #2196F3; color: #fff; }
.btn-info { background: #17a2b8; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
</style>
