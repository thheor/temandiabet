// Approximate nutrition for common Indonesian foods, per the portion shown.
// These are estimates for logging convenience (rounded from typical TKPI-style
// values), not lab values; users can always edit the numbers after picking one.
export interface FoodPreset {
  id: string
  name: string
  portion: string
  calories: number
  carbs: number
}

export const FOOD_PRESETS: FoodPreset[] = [
  // Staples
  { id: 'nasi-putih', name: 'Nasi putih', portion: '1 plate (150 g)', calories: 260, carbs: 58 },
  { id: 'nasi-merah', name: 'Nasi merah', portion: '1 plate (150 g)', calories: 225, carbs: 48 },
  { id: 'lontong', name: 'Lontong', portion: '1 serving (150 g)', calories: 150, carbs: 33 },
  { id: 'bubur-ayam', name: 'Bubur ayam', portion: '1 bowl', calories: 300, carbs: 45 },
  { id: 'nasi-goreng', name: 'Nasi goreng', portion: '1 plate', calories: 400, carbs: 55 },
  { id: 'mie-goreng', name: 'Mie goreng', portion: '1 plate', calories: 480, carbs: 65 },
  { id: 'roti-tawar', name: 'Roti tawar (white bread)', portion: '1 slice', calories: 70, carbs: 13 },
  { id: 'singkong-rebus', name: 'Singkong rebus (boiled cassava)', portion: '100 g', calories: 150, carbs: 36 },
  { id: 'ubi-rebus', name: 'Ubi rebus (boiled sweet potato)', portion: '100 g', calories: 120, carbs: 28 },
  { id: 'kentang-rebus', name: 'Kentang rebus (boiled potato)', portion: '100 g', calories: 80, carbs: 18 },

  // Protein
  { id: 'telur-rebus', name: 'Telur rebus', portion: '1 egg', calories: 75, carbs: 0.6 },
  { id: 'telur-dadar', name: 'Telur dadar', portion: '1 egg', calories: 110, carbs: 1 },
  { id: 'tempe-goreng', name: 'Tempe goreng', portion: '2 pieces (50 g)', calories: 150, carbs: 6 },
  { id: 'tahu-goreng', name: 'Tahu goreng', portion: '2 pieces', calories: 120, carbs: 3 },
  { id: 'ayam-goreng', name: 'Ayam goreng', portion: '1 thigh', calories: 250, carbs: 6 },
  { id: 'ayam-bakar', name: 'Ayam bakar', portion: '1 piece', calories: 200, carbs: 2 },
  { id: 'ikan-goreng', name: 'Ikan goreng', portion: '1 medium piece', calories: 180, carbs: 3 },
  { id: 'rendang', name: 'Rendang daging', portion: '1 piece', calories: 195, carbs: 5 },
  { id: 'sate-ayam', name: 'Sate ayam (peanut sauce)', portion: '10 skewers', calories: 350, carbs: 15 },

  // Soups and vegetables
  { id: 'soto-ayam', name: 'Soto ayam (no rice)', portion: '1 bowl', calories: 200, carbs: 10 },
  { id: 'bakso', name: 'Bakso', portion: '5 balls with broth', calories: 300, carbs: 25 },
  { id: 'gado-gado', name: 'Gado-gado', portion: '1 serving', calories: 300, carbs: 25 },
  { id: 'pecel', name: 'Pecel sayur', portion: '1 serving', calories: 250, carbs: 22 },
  { id: 'sayur-bayam', name: 'Sayur bayam bening', portion: '1 bowl', calories: 50, carbs: 8 },
  { id: 'sayur-asem', name: 'Sayur asem', portion: '1 bowl', calories: 70, carbs: 14 },
  { id: 'capcay', name: 'Capcay', portion: '1 serving', calories: 150, carbs: 12 },

  // Fruit
  { id: 'pisang', name: 'Pisang (banana)', portion: '1 medium', calories: 90, carbs: 23 },
  { id: 'pepaya', name: 'Pepaya (papaya)', portion: '1 slice (100 g)', calories: 45, carbs: 11 },
  { id: 'apel', name: 'Apel (apple)', portion: '1 medium', calories: 70, carbs: 19 },
  { id: 'jeruk', name: 'Jeruk (orange)', portion: '1 medium', calories: 45, carbs: 11 },
  { id: 'semangka', name: 'Semangka (watermelon)', portion: '1 slice', calories: 50, carbs: 11 },

  // Drinks
  { id: 'teh-manis', name: 'Teh manis (sweet tea)', portion: '1 glass', calories: 50, carbs: 13 },
  { id: 'teh-tawar', name: 'Teh tawar (unsweetened tea)', portion: '1 glass', calories: 2, carbs: 0 },
  { id: 'kopi-hitam', name: 'Kopi hitam (black coffee)', portion: '1 cup', calories: 5, carbs: 1 },
  { id: 'susu', name: 'Susu (milk)', portion: '1 glass (200 ml)', calories: 120, carbs: 10 },
]
