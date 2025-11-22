import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import contractService from '../services/contractService';
import Card from '../components/Card';
import Button from '../components/Button';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const ContractDetailsScreen = ({ route, navigation }) => {
  const { contractId } = route.params;
  const [loading, setLoading] = useState(true);
  const [contract, setContract] = useState(null);

  useEffect(() => {
    loadContractDetails();
  }, []);

  const loadContractDetails = async () => {
    try {
      const data = await contractService.getContract(contractId);
      setContract(data.contract || data);
    } catch (error) {
      console.error('Error loading contract details:', error);
      Alert.alert('Erreur', 'Impossible de charger les détails du contrat');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <Loading />;
  }

  if (!contract) {
    return null;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        {/* Contract Info */}
        <Card>
          <View style={styles.header}>
            <Text style={styles.contractNumber}>{contract.contract_number}</Text>
            <StatusBadge status={contract.status} label={contract.status_label} />
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Date de début</Text>
            <Text style={styles.value}>
              {new Date(contract.start_date).toLocaleDateString('fr-FR')}
            </Text>
          </View>

          {contract.end_date && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Date de fin</Text>
              <Text style={styles.value}>
                {new Date(contract.end_date).toLocaleDateString('fr-FR')}
              </Text>
            </View>
          )}

          <View style={styles.infoRow}>
            <Text style={styles.label}>Durée initiale</Text>
            <Text style={styles.value}>{contract.initial_duration_months} mois</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Mode de paiement</Text>
            <Text style={styles.value}>{contract.payment_method_label}</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Jour de prélèvement</Text>
            <Text style={styles.value}>{contract.payment_day}</Text>
          </View>

          {contract.access_code && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Code d'accès</Text>
              <Text style={styles.valueHighlight}>{contract.access_code}</Text>
            </View>
          )}
        </Card>

        {/* Box Info */}
        {contract.box && (
          <Card>
            <Text style={styles.sectionTitle}>Informations du box</Text>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Numéro</Text>
              <Text style={styles.value}>{contract.box.number}</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Volume</Text>
              <Text style={styles.value}>{contract.box.volume} m³</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Surface</Text>
              <Text style={styles.value}>{contract.box.surface} m²</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Dimensions</Text>
              <Text style={styles.value}>
                {contract.box.length} × {contract.box.width} × {contract.box.height} m
              </Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Étage</Text>
              <Text style={styles.value}>{contract.box.floor}</Text>
            </View>

            <View style={styles.infoRow}>
              <Text style={styles.label}>Bâtiment</Text>
              <Text style={styles.value}>{contract.box.building}</Text>
            </View>

            {contract.box.site && (
              <>
                <View style={styles.divider} />
                <Text style={styles.sectionTitle}>Site</Text>

                <View style={styles.infoRow}>
                  <Text style={styles.label}>Nom</Text>
                  <Text style={styles.value}>{contract.box.site.name}</Text>
                </View>

                <View style={styles.infoRow}>
                  <Text style={styles.label}>Adresse</Text>
                  <Text style={styles.value}>
                    {contract.box.site.address}, {contract.box.site.postal_code}{' '}
                    {contract.box.site.city}
                  </Text>
                </View>

                <View style={styles.infoRow}>
                  <Text style={styles.label}>Téléphone</Text>
                  <Text style={styles.value}>{contract.box.site.phone}</Text>
                </View>
              </>
            )}
          </Card>
        )}

        {/* Pricing Info */}
        <Card>
          <Text style={styles.sectionTitle}>Tarification</Text>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Prix mensuel HT</Text>
            <Text style={styles.value}>{contract.price_monthly_ht?.toFixed(2)} €</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>TVA ({contract.tax_rate}%)</Text>
            <Text style={styles.value}>
              {((contract.price_monthly_ht * contract.tax_rate) / 100).toFixed(2)} €
            </Text>
          </View>

          {contract.insurance_monthly > 0 && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Assurance</Text>
              <Text style={styles.value}>{contract.insurance_monthly?.toFixed(2)} €</Text>
            </View>
          )}

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <Text style={styles.labelBold}>Total mensuel TTC</Text>
            <Text style={styles.valuePrice}>{contract.total_monthly_amount?.toFixed(2)} €</Text>
          </View>

          {contract.deposit_amount > 0 && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Caution</Text>
              <Text style={styles.value}>{contract.deposit_amount?.toFixed(2)} €</Text>
            </View>
          )}
        </Card>

        {/* Actions */}
        {contract.status === 'active' && (
          <View style={styles.actions}>
            <Button
              title="Voir les factures"
              onPress={() => navigation.navigate('Invoices')}
              variant="outline"
            />
          </View>
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
  contractNumber: {
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
    flex: 1,
  },
  labelBold: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    flex: 1,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
    flex: 1,
    textAlign: 'right',
  },
  valueHighlight: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
    flex: 1,
    textAlign: 'right',
  },
  valuePrice: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
    flex: 1,
    textAlign: 'right',
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

export default ContractDetailsScreen;
