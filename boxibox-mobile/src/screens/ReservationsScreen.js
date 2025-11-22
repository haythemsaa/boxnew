import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import reservationService from '../services/reservationService';
import Card from '../components/Card';
import Button from '../components/Button';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const ReservationsScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [reservations, setReservations] = useState([]);

  useEffect(() => {
    loadReservations();
  }, []);

  const loadReservations = async () => {
    try {
      const data = await reservationService.getReservations();
      setReservations(data.reservations || data);
    } catch (error) {
      console.error('Error loading reservations:', error);
      Alert.alert('Erreur', 'Impossible de charger les réservations');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadReservations();
  };

  const handleCancelReservation = (reservationId) => {
    Alert.alert(
      'Annuler la réservation',
      'Êtes-vous sûr de vouloir annuler cette réservation ?',
      [
        { text: 'Non', style: 'cancel' },
        {
          text: 'Oui',
          style: 'destructive',
          onPress: async () => {
            try {
              await reservationService.cancelReservation(reservationId);
              Alert.alert('Succès', 'Réservation annulée');
              loadReservations();
            } catch (error) {
              console.error('Error canceling reservation:', error);
              Alert.alert('Erreur', 'Impossible d\'annuler la réservation');
            }
          },
        },
      ]
    );
  };

  const renderReservation = ({ item }) => (
    <Card>
      <View style={styles.reservationHeader}>
        <Text style={styles.reservationNumber}>{item.reservation_number}</Text>
        <StatusBadge status={item.status} label={item.status.toUpperCase()} />
      </View>

      <Text style={styles.boxNumber}>Box {item.box_number}</Text>
      <Text style={styles.siteName}>{item.site_name}</Text>

      <View style={styles.details}>
        <View style={styles.detailRow}>
          <Text style={styles.label}>Date de début</Text>
          <Text style={styles.value}>
            {new Date(item.start_date).toLocaleDateString('fr-FR')}
          </Text>
        </View>

        <View style={styles.detailRow}>
          <Text style={styles.label}>Durée</Text>
          <Text style={styles.value}>{item.duration_months} mois</Text>
        </View>

        <View style={styles.detailRow}>
          <Text style={styles.label}>Prix mensuel TTC</Text>
          <Text style={styles.value}>{item.total_monthly_ttc?.toFixed(2)} €</Text>
        </View>

        <View style={styles.divider} />

        <View style={styles.detailRow}>
          <Text style={styles.labelBold}>Premier paiement</Text>
          <Text style={styles.valueBold}>{item.first_payment?.toFixed(2)} €</Text>
        </View>
      </View>

      {item.status === 'pending' && (
        <>
          <Text style={styles.expiryWarning}>
            Expire le {new Date(item.expires_at).toLocaleDateString('fr-FR')}
          </Text>
          <Button
            title="Annuler la réservation"
            onPress={() => handleCancelReservation(item.id)}
            variant="outline"
            size="small"
            style={styles.cancelButton}
          />
        </>
      )}
    </Card>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Button
          title="+ Nouvelle réservation"
          onPress={() => navigation.navigate('SearchBoxes')}
          size="medium"
        />
      </View>

      <FlatList
        data={reservations}
        renderItem={renderReservation}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucune réservation</Text>
            <Button
              title="Rechercher un box"
              onPress={() => navigation.navigate('SearchBoxes')}
              style={styles.searchButton}
            />
          </Card>
        }
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  header: {
    padding: 16,
    paddingBottom: 8,
  },
  listContent: {
    padding: 16,
    paddingTop: 8,
  },
  reservationHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  reservationNumber: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.secondary,
  },
  boxNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  siteName: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
  },
  details: {
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  detailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  label: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
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
  divider: {
    height: 1,
    backgroundColor: COLORS.divider,
    marginVertical: 8,
  },
  expiryWarning: {
    fontSize: 12,
    color: COLORS.warning,
    marginTop: 8,
    fontStyle: 'italic',
  },
  cancelButton: {
    marginTop: 12,
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
    marginBottom: 16,
  },
  searchButton: {
    marginTop: 8,
  },
});

export default ReservationsScreen;
