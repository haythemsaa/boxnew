import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import reservationService from '../services/reservationService';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import { COLORS } from '../constants/colors';

const BoxDetailsScreen = ({ route, navigation }) => {
  const { box, searchParams } = route.params;
  const [calculating, setCalculating] = useState(false);
  const [reserving, setReserving] = useState(false);
  const [promoCode, setPromoCode] = useState('');
  const [pricing, setPricing] = useState(box.pricing);
  const [withInsurance, setWithInsurance] = useState(false);

  const handleCalculatePrice = async () => {
    setCalculating(true);

    try {
      const data = await reservationService.calculatePrice({
        box_id: box.id,
        duration_months: parseInt(searchParams.duration_months),
        promo_code: promoCode || undefined,
        insurance: withInsurance,
      });

      setPricing(data.pricing);

      if (data.promotion) {
        Alert.alert(
          'Promotion appliquée',
          `${data.promotion.name}: ${data.promotion.description}`
        );
      }
    } catch (error) {
      console.error('Error calculating price:', error);
      Alert.alert('Erreur', error.message || 'Impossible de calculer le prix');
    } finally {
      setCalculating(false);
    }
  };

  const handleReserve = async () => {
    Alert.alert(
      'Confirmer la réservation',
      `Voulez-vous réserver le box ${box.number} ?\n\nPremier paiement: ${pricing?.first_payment?.toFixed(2)} €`,
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Confirmer',
          onPress: async () => {
            setReserving(true);

            try {
              const data = await reservationService.createReservation({
                box_id: box.id,
                start_date: new Date().toISOString().split('T')[0],
                duration_months: parseInt(searchParams.duration_months),
                promo_code: promoCode || undefined,
                insurance: withInsurance,
              });

              Alert.alert(
                'Réservation créée',
                `Votre réservation ${data.reservation.reservation_number} a été créée avec succès.`,
                [
                  {
                    text: 'OK',
                    onPress: () => navigation.navigate('Reservations'),
                  },
                ]
              );
            } catch (error) {
              console.error('Error creating reservation:', error);
              Alert.alert('Erreur', error.message || 'Impossible de créer la réservation');
            } finally {
              setReserving(false);
            }
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        <Card>
          <Text style={styles.boxNumber}>Box {box.number}</Text>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Volume</Text>
            <Text style={styles.value}>{box.volume} m³</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Surface</Text>
            <Text style={styles.value}>{box.surface} m²</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Dimensions</Text>
            <Text style={styles.value}>{box.dimensions}</Text>
          </View>
        </Card>

        {box.site && (
          <Card>
            <Text style={styles.sectionTitle}>Site</Text>
            <Text style={styles.siteName}>{box.site.name}</Text>
            <Text style={styles.siteAddress}>
              {box.site.address}, {box.site.city}
            </Text>
          </Card>
        )}

        {box.features && (
          <Card>
            <Text style={styles.sectionTitle}>Caractéristiques</Text>
            {box.features.climate_controlled && (
              <Text style={styles.feature}>❄️ Climatisé</Text>
            )}
            {box.features.ground_floor && (
              <Text style={styles.feature}>🏢 Rez-de-chaussée</Text>
            )}
            {box.features.vehicle_access && (
              <Text style={styles.feature}>🚗 Accès véhicule</Text>
            )}
            {box.features.has_electricity && (
              <Text style={styles.feature}>⚡ Électricité</Text>
            )}
          </Card>
        )}

        <Card>
          <Text style={styles.sectionTitle}>Options</Text>

          <TouchableOpacity
            style={styles.checkbox}
            onPress={() => setWithInsurance(!withInsurance)}
          >
            <View style={[styles.checkboxBox, withInsurance && styles.checkboxChecked]}>
              {withInsurance && <Text style={styles.checkboxCheck}>✓</Text>}
            </View>
            <Text style={styles.checkboxLabel}>Assurance incluse</Text>
          </TouchableOpacity>

          <Input
            label="Code promo (optionnel)"
            value={promoCode}
            onChangeText={setPromoCode}
            placeholder="BIENVENUE30"
            autoCapitalize="characters"
          />

          <Button
            title="Calculer le prix"
            onPress={handleCalculatePrice}
            loading={calculating}
            variant="outline"
          />
        </Card>

        {pricing && (
          <Card>
            <Text style={styles.sectionTitle}>Tarification</Text>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Prix mensuel HT</Text>
              <Text style={styles.value}>{pricing.monthly_price_ht?.toFixed(2)} €</Text>
            </View>

            {pricing.discount_amount > 0 && (
              <View style={styles.infoRow}>
                <Text style={styles.label}>Réduction</Text>
                <Text style={[styles.value, styles.discount]}>
                  -{pricing.discount_amount?.toFixed(2)} €
                </Text>
              </View>
            )}

            {withInsurance && pricing.insurance_monthly > 0 && (
              <View style={styles.infoRow}>
                <Text style={styles.label}>Assurance mensuelle</Text>
                <Text style={styles.value}>{pricing.insurance_monthly?.toFixed(2)} €</Text>
              </View>
            )}

            <View style={styles.infoRow}>
              <Text style={styles.label}>TVA ({pricing.tax_rate}%)</Text>
              <Text style={styles.value}>
                {((pricing.monthly_price_ht * pricing.tax_rate) / 100).toFixed(2)} €
              </Text>
            </View>

            <View style={styles.divider} />

            <View style={styles.infoRow}>
              <Text style={styles.labelBold}>Total mensuel TTC</Text>
              <Text style={styles.valueBold}>{pricing.total_monthly_ttc?.toFixed(2)} €</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Caution</Text>
              <Text style={styles.value}>{pricing.deposit_amount?.toFixed(2)} €</Text>
            </View>

            <View style={styles.divider} />

            <View style={styles.infoRow}>
              <Text style={styles.labelBold}>Premier paiement</Text>
              <Text style={[styles.valueBold, styles.firstPayment]}>
                {pricing.first_payment?.toFixed(2)} €
              </Text>
            </View>
          </Card>
        )}

        <View style={styles.actions}>
          <Button
            title="Réserver ce box"
            onPress={handleReserve}
            loading={reserving}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  boxNumber: {
    fontSize: 24,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  label: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
    fontWeight: '600',
  },
  labelBold: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
  },
  valueBold: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  firstPayment: {
    color: COLORS.success,
  },
  discount: {
    color: COLORS.success,
  },
  siteName: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  siteAddress: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  feature: {
    fontSize: 14,
    color: COLORS.text.primary,
    marginBottom: 8,
  },
  checkbox: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  checkboxBox: {
    width: 24,
    height: 24,
    borderWidth: 2,
    borderColor: COLORS.border,
    borderRadius: 4,
    marginRight: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxChecked: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  checkboxCheck: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: 'bold',
  },
  checkboxLabel: {
    fontSize: 14,
    color: COLORS.text.primary,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.divider,
    marginVertical: 12,
  },
  actions: {
    marginBottom: 24,
  },
});

export default BoxDetailsScreen;
