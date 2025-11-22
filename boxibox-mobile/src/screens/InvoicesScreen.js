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
import invoiceService from '../services/invoiceService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const InvoicesScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [invoices, setInvoices] = useState([]);

  useEffect(() => {
    loadInvoices();
  }, []);

  const loadInvoices = async () => {
    try {
      const data = await invoiceService.getInvoices();
      setInvoices(data.invoices || data);
    } catch (error) {
      console.error('Error loading invoices:', error);
      Alert.alert('Erreur', 'Impossible de charger les factures');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadInvoices();
  };

  const renderInvoice = ({ item }) => (
    <TouchableOpacity
      onPress={() => navigation.navigate('InvoiceDetails', { invoiceId: item.id })}
    >
      <Card>
        <View style={styles.invoiceHeader}>
          <Text style={styles.invoiceNumber}>{item.invoice_number}</Text>
          <StatusBadge status={item.status} label={item.status_label} />
        </View>

        <View style={styles.invoiceDetails}>
          <View>
            <Text style={styles.label}>Date de facturation</Text>
            <Text style={styles.value}>
              {new Date(item.invoice_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>

          <View>
            <Text style={styles.label}>Échéance</Text>
            <Text style={styles.value}>
              {new Date(item.due_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>
        </View>

        <View style={styles.invoiceFooter}>
          <View>
            <Text style={styles.label}>Montant TTC</Text>
            <Text style={styles.totalAmount}>{item.total_ttc?.toFixed(2)} €</Text>
          </View>

          {item.remaining_amount > 0 && (
            <View>
              <Text style={styles.label}>Reste à payer</Text>
              <Text style={styles.remainingAmount}>{item.remaining_amount?.toFixed(2)} €</Text>
            </View>
          )}
        </View>

        {item.contract_number && (
          <Text style={styles.contractRef}>Contrat: {item.contract_number}</Text>
        )}
      </Card>
    </TouchableOpacity>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <FlatList
        data={invoices}
        renderItem={renderInvoice}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucune facture</Text>
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
  invoiceHeader: {
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
  invoiceDetails: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  label: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
  },
  invoiceFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
    marginBottom: 8,
  },
  totalAmount: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  remainingAmount: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.error,
  },
  contractRef: {
    fontSize: 12,
    color: COLORS.text.secondary,
    fontStyle: 'italic',
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default InvoicesScreen;
