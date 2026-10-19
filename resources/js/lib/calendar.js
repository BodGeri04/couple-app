export const CATEGORIES = [
    { value: 'anniversary', label: 'Évforduló', emoji: '💍' },
    { value: 'milestone', label: 'Mérföldkő', emoji: '🌟' },
    { value: 'date', label: 'Randi', emoji: '🌹' },
    { value: 'special', label: 'Különleges', emoji: '💗' },
];

export const categoryOf = (value) => CATEGORIES.find((c) => c.value === value) ?? CATEGORIES[3];