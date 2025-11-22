import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import invoiceService from '../services/invoiceService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const InvoiceDetailsScreen = ({ route, navigation }) => {
  const { invoiceId } = route.params;
  const [loading, setLoading] = useState(true);
  const [invoice, setInvoice] = useState(null);

  useEffect(() => {
    loadInvoiceDetails();
  }, []);

  const loadInvoiceDetails = async () => {
    try {
      const data = await invoiceService.getInvoice(invoiceId);
      setInvoice(data.invoice || data);
    } catch (error) {
      console.error('Error loading invoice details:', error);
      Alert.alert('Erreur', 'Impossible de charger les détails de la facture');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <Loading />;
  }

  if (!invoice) {
    return null;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        {/* Invoice Info */}
        <Card>
          <View style={styles.header}>
            <Text style={styles.invoiceNumber}>{invoice.invoice_number}</Text>
            <StatusBadge status={invoice.status} label={invoice.status_label} />
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Date de facturation</Text>
            <Text style={styles.value}>
              {new Date(invoice.invoice_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Date d'échéance</Text>
            <Text style={styles.value}>
              {new Date(invoice.due_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>

          {invoice.paid_at && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Date de paiement</Text>
              <Text style={styles.value}>
                {new Date(invoice.paid_at).toLocaleDateString('fr-FR')}
              </Text>
            </View>
          )}

          {invoice.contract && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Contrat</Text>
              <Text style={styles.value}>
                {invoice.contract.contract_number}
                {invoice.contract.box_number && ` - Box ${invoice.contract.box_number}`}
              </Text>
            </View>
          )}
        </Card>

        {/* Line Items */}
        {invoice.line_items && invoice.line_items.length > 0 && (
          <Card>
            <Text style={styles.sectionTitle}>Détails</Text>
            {invoice.line_items.map((item, index) => (
              <View key={index} style={styles.lineItem}>
                <View style={styles.lineItemDetails}>
                  <Text style={styles.lineItemDescription}>{item.description}</Text>
                  <Text style={styles.lineItemQty}>Qté: {item.quantity}</Text>
                </View>
                <Text style={styles.lineItemTotal}>{item.total?.toFixed(2)} €</Text>
              </View>
            ))}
          </Card>
        )}

        {/* Totals */}
        <Card>
          <Text style={styles.sectionTitle}>Montants</Text>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Total HT</Text>
            <Text style={styles.value}>{invoice.total_ht?.toFixed(2)} €</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>TVA ({invoice.tax_rate}%)</Text>
            <Text style={styles.value}>{invoice.tax_amount?.toFixed(2)} €</Text>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <Text style={styles.labelBold}>Total TTC</Text>
            <Text style={styles.valueTotal}>{invoice.total_ttc?.toFixed(2)} €</Text>
          </View>

          {invoice.paid_amount > 0 && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Montant payé</Text>
              <Text style={[styles.value, styles.paidAmount]}>
                {invoice.paid_amount?.toFixed(2)} €
              </Text>
            </View>
          )}

          {invoice.remaining_amount > 0 && (
            <View style={styles.infoRow}>
              <Text style={styles.labelBold}>Reste à payer</Text>
              <Text style={[styles.valueTotal, styles.remainingAmount]}>
                {invoice.remaining_amount?.toFixed(2)} €
              </Text>
            </View>
          )}
        </Card>

        {/* Payments */}
        {invoice.payments && invoice.payments.length > 0 && (
          <Card>
            <Text style={styles.sectionTitle}>Paiements</Text>
            {invoice.payments.map((payment) => (
              <View key={payment.id} style={styles.paymentItem}>
                <View>
                  <Text style={styles.paymentNumber}>{payment.payment_number}</Text>
                  <Text style={styles.paymentDetails}>
                    {new Date(payment.payment_date).toLocaleDateString('fr-FR')} •{' '}
                    {payment.method_label}
                  </Text>
                </View>
                <View style={styles.paymentRight}>
                  <Text style={styles.paymentAmount}>{payment.amount?.toFixed(2)} €</Text>
                  <StatusBadge status={payment.status} label={payment.status_label} />
                </View>
              </View>
            ))}
          </Card>
        )}

        {invoice.notes && (
          <Card>
            <Text style={styles.sectionTitle}>Notes</Text>
            <Text style={styles.notes}>{invoice.notes}</Text>
          </Card>
        )}
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
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  invoiceNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  label: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  labelBold: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
    textAlign: 'right',
  },
  valueTotal: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  paidAmount: {
    color: COLORS.success,
  },
  remainingAmount: {
    color: COLORS.error,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.divider,
    marginVertical: 8,
  },
  lineItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.divider,
  },
  lineItemDetails: {
    flex: 1,
    paddingRight: 12,
  },
  lineItemDescription: {
    fontSize: 14,
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  lineItemQty: {
    fontSize: 12,
    color: COLORS.text.secondary,
  },
  lineItemTotal: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  paymentItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.divider,
  },
  paymentNumber: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  paymentDetails: {
    fontSize: 12,
    color: COLORS.text.secondary,
  },
  paymentRight: {
    alignItems: 'flex-end',
  },
  paymentAmount: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.success,
    marginBottom: 4,
  },
  notes: {
    fontSize: 14,
    color: COLORS.text.primary,
    lineHeight: 20,
  },
});

export default InvoiceDetailsScreen;
