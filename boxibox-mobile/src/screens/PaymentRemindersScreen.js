import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import paymentReminderService from '../services/paymentReminderService';
import Card from '../components/Card';
import Button from '../components/Button';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const PaymentRemindersScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [reminders, setReminders] = useState([]);

  useEffect(() => {
    loadReminders();
  }, []);

  const loadReminders = async () => {
    try {
      const data = await paymentReminderService.getReminders();
      setReminders(data.reminders || data);
    } catch (error) {
      console.error('Error loading reminders:', error);
      Alert.alert('Erreur', 'Impossible de charger les rappels de paiement');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadReminders();
  };

  const handleAcknowledge = async (reminderId) => {
    try {
      await paymentReminderService.acknowledgeReminder(reminderId);
      Alert.alert('Succès', 'Rappel accusé réception');
      loadReminders();
    } catch (error) {
      console.error('Error acknowledging reminder:', error);
      Alert.alert('Erreur', error.message || 'Impossible d\'accuser réception');
    }
  };

  const getSeverityColor = (severity) => {
    switch (severity) {
      case 'high':
        return COLORS.error;
      case 'medium':
        return COLORS.warning;
      case 'low':
        return COLORS.info;
      default:
        return COLORS.gray[500];
    }
  };

  const renderReminder = ({ item }) => (
    <Card>
      <View style={styles.reminderHeader}>
        <Text style={styles.invoiceNumber}>{item.invoice_number}</Text>
        <StatusBadge status={item.status} label={item.status_label} />
      </View>

      <View style={[styles.phaseIndicator, { backgroundColor: getSeverityColor(item.severity) + '20' }]}>
        <Text style={[styles.phaseName, { color: getSeverityColor(item.severity) }]}>
          {item.phase_name}
        </Text>
      </View>

      <View style={styles.reminderDetails}>
        <View style={styles.detailRow}>
          <Text style={styles.label}>Jours de retard</Text>
          <Text style={styles.value}>{item.days_overdue} jours</Text>
        </View>

        <View style={styles.detailRow}>
          <Text style={styles.label}>Montant dû</Text>
          <Text style={styles.value}>{item.amount_due?.toFixed(2)} €</Text>
        </View>

        {item.late_fee > 0 && (
          <View style={styles.detailRow}>
            <Text style={styles.label}>Pénalité</Text>
            <Text style={[styles.value, styles.lateFee]}>{item.late_fee?.toFixed(2)} €</Text>
          </View>
        )}

        <View style={styles.divider} />

        <View style={styles.detailRow}>
          <Text style={styles.labelBold}>Total à payer</Text>
          <Text style={styles.valueBold}>{item.total_amount?.toFixed(2)} €</Text>
        </View>
      </View>

      {item.contract_number && (
        <Text style={styles.contractRef}>
          Contrat: {item.contract_number} - Box {item.box_number}
        </Text>
      )}

      {item.status === 'sent' && !item.acknowledged_at && (
        <Button
          title="Accuser réception"
          onPress={() => handleAcknowledge(item.id)}
          variant="outline"
          size="small"
          style={styles.acknowledgeButton}
        />
      )}

      {item.acknowledged_at && (
        <Text style={styles.acknowledgedText}>
          Accusé réception le {new Date(item.acknowledged_at).toLocaleDateString('fr-FR')}
        </Text>
      )}
    </Card>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <FlatList
        data={reminders}
        renderItem={renderReminder}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucun rappel de paiement</Text>
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
  listContent: {
    padding: 16,
  },
  reminderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  invoiceNumber: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
  },
  phaseIndicator: {
    padding: 8,
    borderRadius: 8,
    marginBottom: 12,
  },
  phaseName: {
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
  reminderDetails: {
    marginBottom: 12,
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
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  valueBold: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.error,
  },
  lateFee: {
    color: COLORS.error,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.divider,
    marginVertical: 8,
  },
  contractRef: {
    fontSize: 12,
    color: COLORS.text.secondary,
    fontStyle: 'italic',
    marginBottom: 8,
  },
  acknowledgeButton: {
    marginTop: 8,
  },
  acknowledgedText: {
    fontSize: 12,
    color: COLORS.success,
    textAlign: 'center',
    marginTop: 8,
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default PaymentRemindersScreen;
